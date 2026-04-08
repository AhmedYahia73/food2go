{{-- checkout.blade.php --}}
<script src="{{ $hppScript }}"></script>
<script>
    GeideaCheckout.startPayment({
        sessionId:   "{{ $sessionId }}",
        merchantKey: "{{ $merchantKey }}",
    });
</script>