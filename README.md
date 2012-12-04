<h2>Ajax File Upload Component for CakePHP 2.x</h2>

This component is created using <a href="http://fineuploader.com/">FineUploader</a>


<h3>Instruction for use:</h3>
Copy css,js and imaged to their respective folders.
Copy AjaxFileUploadComponent.php to component
<h3>Basic Example :</h3>

<pre><span class="nt">&lt;div</span> <span class="na">id=</span><span class="s">"fine-uploader"</span><span class="nt">&gt;</span>
<span class="nt">&lt;noscript&gt;</span>
    <span class="nt">&lt;p&gt;</span>Please enable JavaScript to use Fine Uploader.<span class="nt">&lt;/p&gt;</span>
    <span class="c">&lt;!-- or put a simple form for upload here --&gt;</span>
<span class="nt">&lt;/noscript&gt;</span>
<span class="nt">&lt;/div&gt;</span>
</pre>

<pre>
$('#fine-uploader').fineUploader({
    request: {
        endpoint: '/controller/action'
    }
}).on('error', function(event, id, filename, reason) {
     //do something
  })
  .on('complete', function(event, id, filename, responseJSON){
    //do something
  });
</pre>

<h4>You can find detailed documentation about file uploader <a href="https://github.com/valums/file-uploader/">here</a>.</h4>