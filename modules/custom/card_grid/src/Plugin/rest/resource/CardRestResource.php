<?php

namespace Drupal\card_grid\Plugin\rest\resource;

use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "card_rest_resource",
 *   label = @Translation("Card rest resource"),
 *   uri_paths = {
 *     "canonical" = "/code-challenge/card-grid"
 *   }
 * )
 */
class CardRestResource extends ResourceBase {

  /**
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;
  /**
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->logger = $container->get('logger.factory')->get('card_grid');
    $instance->currentUser = $container->get('current_user');
    $instance->currentRequest =  $container->get('request_stack')->getCurrentRequest();
    return $instance;
  }

  /**
     * Responds to GET requests.
     *
     * @param string $payload
     *
     * @return \Drupal\rest\ResourceResponse
     *   The HTTP response object.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     *   Throws exception expected.
  */
  public function get($payload) {
        $message = "";
        $rows = 0;
        $columns = 0;
        
        
        // You must implement the logic of your REST Resource here.
        // Use current user after pass authentication to validate access.
        if (!$this->currentUser->hasPermission('access content')) {
            throw new AccessDeniedHttpException();
        }
        // get request parameters rows and columns
        $rows = $this->currentRequest->query->get('rows');
        $columns = $this->currentRequest->query->get('columns');

        if ($this->validateRequest($rows,$columns,$message) ) {

            // Shuffle alphabets
            $alphabet = ["A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P", "Q","R","S","T","U","V","W","X","Y","Z"];
            for ($k = count($alphabet) - 1 ; $k > 0 ; $k--) {
                  $z = rand($k/2,$k);
                  $temp = $alphabet[$z];
                  $alphabet[$z] = $alphabet[$k];
                  $alphabet[$k] = $temp;
            }
            // Create unique array of cards
            $numElements = $rows * $columns;
            $unique = array();
            for( $k=0; $k < $numElements/2; $k++) {
                  
                  $unique[$k] = $alphabet[$numElements/2 - $k];
                  $unique[$numElements/2 + $k] = $unique[$k];
            }
         
            // Populate the response
            $k = 0;
            for ($i = 0; $i < $rows; $i++) {
            
                  for ($j =0; $j < $columns ; $j++) {
            
                        $output[$i][$j] =  $unique[$k];
                        $k++;
                  }
            }
           
            $return = [
               'success' => 'true',
               'cardcount' => $numElements,
               'uniqueCardCount' => $numElements/2,
               'uniqueCards' => array_unique($unique)
            ];
            $cardData = ['cards' => $output];
            $responseData[] = ['meta' => $return,
                               'data' => $cardData];
           
            $response = new ResourceResponse($responseData, 200);
            
        }
        else {
            $return = [
              'success' => 'false',
              'message' => $message
            ];
            
            $responseData[] = ['meta' => $return,
                               'data' => []];
            $response =  new ResourceResponse($responseData, 200);
        }
        $response->addCacheableDependency($responseData);
        return $response;
  }
  public function validateRequest($rows,$columns,&$msg) {
      
    
    if ($rows <= 0 || $rows > 6 || $columns <= 0 || $columns > 6) {
      $msg = "Both paramters (rows,columns) are required with value between 0 and 6. " .
                                   $rows . "   " . $columns ;
      
    }
    elseif ( ($rows % 2) != 0  && ($columns % 2) != 0 ) {
      $msg = "Either rows or columns needs to be an even number.";
    }
    else {
      return true;
    }
    return false;

  }

}
