<?php
/**
 * Formulaires de connexion et d'inscription (Mon compte).
 *
 * Remplace woocommerce/myaccount/form-login.php.
 *
 * @package WooCommerce\Templates
 * @version 9.9.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$inscription = 'yes' === get_option( 'woocommerce_enable_myaccount_registration' );
$mdp_genere  = 'no' !== get_option( 'woocommerce_registration_generate_password' );
$pseudo_requis = 'no' === get_option( 'woocommerce_registration_generate_username' );

do_action( 'woocommerce_before_customer_login_form' );
?>

<div class="gb-login<?php echo $inscription ? ' gb-login--duo' : ''; ?>" id="customer_login">

	<section class="gb-login__col">
		<h2 class="gb-login__title">Connexion</h2>

		<form class="woocommerce-form woocommerce-form-login login" method="post" novalidate>
			<?php do_action( 'woocommerce_login_form_start' ); ?>

			<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
				<label for="username">Adresse e-mail ou identifiant</label>
				<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="username" id="username" autocomplete="username" value="<?php echo ( ! empty( $_POST['username'] ) && is_string( $_POST['username'] ) ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : ''; ?>" required aria-required="true" />
			</p>
			<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
				<label for="password">Mot de passe</label>
				<input class="woocommerce-Input woocommerce-Input--text input-text" type="password" name="password" id="password" autocomplete="current-password" required aria-required="true" />
			</p>

			<?php do_action( 'woocommerce_login_form' ); ?>

			<div class="gb-login__options">
				<label class="woocommerce-form__label woocommerce-form__label-for-checkbox woocommerce-form-login__rememberme">
					<input class="woocommerce-form__input woocommerce-form__input-checkbox" name="rememberme" type="checkbox" id="rememberme" value="forever" />
					<span>Se souvenir de moi</span>
				</label>
				<a class="gb-login__lost" href="<?php echo esc_url( wp_lostpassword_url() ); ?>">Mot de passe oublié ?</a>
			</div>

			<p class="form-row gb-login__submit">
				<?php wp_nonce_field( 'woocommerce-login', 'woocommerce-login-nonce' ); ?>
				<button type="submit" class="woocommerce-button button woocommerce-form-login__submit" name="login" value="Se connecter">Se connecter</button>
			</p>

			<?php do_action( 'woocommerce_login_form_end' ); ?>
		</form>
	</section>

	<?php if ( $inscription ) : ?>
		<section class="gb-login__col">
			<h2 class="gb-login__title">Créer un compte</h2>

			<form method="post" class="woocommerce-form woocommerce-form-register register" <?php do_action( 'woocommerce_register_form_tag' ); ?> >
				<?php do_action( 'woocommerce_register_form_start' ); ?>

				<?php if ( $pseudo_requis ) : ?>
					<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
						<label for="reg_username">Identifiant</label>
						<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="username" id="reg_username" autocomplete="username" value="<?php echo ( ! empty( $_POST['username'] ) ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : ''; ?>" required aria-required="true" />
					</p>
				<?php endif; ?>

				<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
					<label for="reg_email">Adresse e-mail</label>
					<input type="email" class="woocommerce-Input woocommerce-Input--text input-text" name="email" id="reg_email" autocomplete="email" value="<?php echo ( ! empty( $_POST['email'] ) ) ? esc_attr( wp_unslash( $_POST['email'] ) ) : ''; ?>" required aria-required="true" />
				</p>

				<?php if ( ! $mdp_genere ) : ?>
					<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
						<label for="reg_password">Mot de passe</label>
						<input type="password" class="woocommerce-Input woocommerce-Input--text input-text" name="password" id="reg_password" autocomplete="new-password" required aria-required="true" />
					</p>
				<?php else : ?>
					<p class="gb-login__note">Un lien pour définir votre mot de passe vous sera envoyé par e-mail.</p>
				<?php endif; ?>

				<?php do_action( 'woocommerce_register_form' ); ?>

				<p class="woocommerce-form-row form-row gb-login__submit">
					<?php wp_nonce_field( 'woocommerce-register', 'woocommerce-register-nonce' ); ?>
					<button type="submit" class="woocommerce-Button woocommerce-button button woocommerce-form-register__submit" name="register" value="Créer mon compte">Créer mon compte</button>
				</p>

				<?php do_action( 'woocommerce_register_form_end' ); ?>
			</form>
		</section>
	<?php endif; ?>

</div>

<?php do_action( 'woocommerce_after_customer_login_form' ); ?>
