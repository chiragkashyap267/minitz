<form method="post" action="/contact" class="contact-form" id="contact-us">@csrf
<h2>Say hello.</h2><p>Questions, feedback or a little kitchen chat? Drop us a message.</p>
@if(session('contact_success'))<p class="contact-success" role="status">{{ session('contact_success') }}</p>@endif
<input type="hidden" name="source" value="{{ $source }}">
<label>Your name<input name="name" autocomplete="name" maxlength="100" required value="{{ old('name') }}" placeholder="e.g. Priya Sharma"></label>
<label>Email address<input type="email" name="email" autocomplete="email" maxlength="150" required value="{{ old('email') }}" placeholder="e.g. priya@example.com"></label>
<label>Your message<textarea name="message" rows="4" maxlength="5000" required placeholder="Tell us what’s on your mind…">{{ old('message') }}</textarea></label>
<button class="button button-blue">Send message</button>
<p class="contact-privacy">Your details are used to respond to your message. <a href="/pages/privacy">Privacy policy</a></p>
</form>