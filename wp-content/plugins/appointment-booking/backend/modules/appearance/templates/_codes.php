<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
    $codes = array(
        array(
            'code'        => '[[CATEGORY_NAME]]',
            'description' => __( 'name of category', 'bookly' ),
            'order'       => 10,
        ),
        array(
            'code'        => '[[LOGIN_FORM]]',
            'description' => __( 'login form', 'bookly' ),
            'order'       => 20,
            'step'        => 5,
            'login'       => true,
        ),
        array(
            'code'        => '[[NUMBER_OF_PERSONS]]',
            'description' => __( 'number of persons', 'bookly' ),
            'order'       => 30,
        ),
        array(
            'code'        => '[[BOOKING_NUMBER]]',
            'description' => __( 'booking number', 'bookly' ),
            'order'       => 40,
            'step'        => 7,
        ),
        array(
            'code'        => '[[SERVICE_DATE]]',
            'description' => __( 'date of service', 'bookly' ),
            'order'       => 50,
            'min_step'    => 3,
        ),
        array(
            'code'        => '[[SERVICE_INFO]]',
            'description' => __( 'info of service', 'bookly' ),
            'order'       => 60,
        ),
        array(
            'code'        => '[[SERVICE_NAME]]',
            'description' => __( 'name of service', 'bookly' ),
            'order'       => 70,
        ),
        array(
            'code'        => '[[SERVICE_PRICE]]',
            'description' => __( 'price of service', 'bookly' ),
            'order'       => 80,
        ),
        array(
            'code'        => '[[SERVICE_TIME]]',
            'description' => __( 'time of service', 'bookly' ),
            'order'       => 90,
            'min_step'    => 3,
        ),
        array(
            'code'        => '[[STAFF_INFO]]',
            'description' => __( 'info of staff', 'bookly' ),
            'order'       => 100,
        ),
        array(
            'code'        => '[[STAFF_NAME]]',
            'description' => __( 'name of staff', 'bookly' ),
            'order'       => 110,
        ),
        array(
            'code'        => '[[TOTAL_PRICE]]',
            'description' => __( 'total price of booking', 'bookly' ),
            'order'       => 120,
        ),
    );

    $codes = apply_filters( 'bookly_appearance_short_codes', $codes );

    uasort( $codes, function( $v1, $v2 ){
        if ( $v1['order'] == $v2['order'] ) {
            return 0;
        }
        return ( $v1['order'] < $v2['order'] ) ? -1 : 1;
    } );
?>

<?php foreach ( $codes as $code ) : ?>
    <?php if ( empty( $code['step'] ) || $step == $code['step'] ) : ?>
        <?php if ( empty( $code['min_step'] ) || $step > $code['min_step'] ) : ?>
            <?php if ( empty( $code['login'] ) || $login ) : ?>
                <b><?php echo $code['code'] ?></b> - <?php echo $code['description'] ?><br/>
            <?php endif ?>
        <?php endif ?>
    <?php endif ?>
<?php endforeach ?>