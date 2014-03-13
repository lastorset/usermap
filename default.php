<script type="text/javascript">
jQuery( document ).ready(function() {
   load();
});
</script>
 <script type="text/javascript">
        //<![CDATA[
        var customIcons = {};

        function load() {
            var map = new google.maps.Map(document.getElementById("map"), {
             center: new google.maps.LatLng(54.525961400000000000,15.255118700000025000),
             zoom:1,
                mapTypeId: 'roadmap'
           });
             var maponline = new google.maps.Map(document.getElementById("maponline"), {
                center: new google.maps.LatLng(54.525961400000000000,15.255118700000025000),
                zoom:1,
                mapTypeId: 'roadmap'
            });
            var infoWindow = new google.maps.InfoWindow;
			
			
		var image = {
					url: '<?php echo plugins_url( "images/red-dot.png", __FILE__ ); ?>',
					// This marker is 20 pixels wide by 32 pixels tall.
					size: new google.maps.Size(25, 32),
					// The origin for this image is 0,0.
					origin: new google.maps.Point(0,0),
					// The anchor for this image is the base of the flagpole at 0,32.
					//anchor: new google.maps.Point(0, 32)
			};
            //Change this depending on the name of your PHP file
          downloadUrl("wp-admin/admin-ajax.php?action=mapregisteruser", function(data) {
			 
               var xml = data.responseXML;
               var markers = xml.documentElement.getElementsByTagName("marker");
                for (var i = 0; i < markers.length; i++) {
					// alert(markers[i].getAttribute("lat"));
                   var name = markers[i].getAttribute("name");
                 var address = markers[i].getAttribute("address");
                   var type = markers[i].getAttribute("type");
                    var point = new google.maps.LatLng(
                            parseFloat(markers[i].getAttribute("lat")),
                            parseFloat(markers[i].getAttribute("lng")));
                    var html = "<b>" + name + "</b> <br/>" + address;
                    var icon = customIcons[type] || {};
                    var marker = new google.maps.Marker({
                        map: map,
						center: new google.maps.LatLng(markers[i].getAttribute("lat"), markers[i].getAttribute("lng")),
                        position: point,
                        icon: image,
                    });
                    bindInfoWindow(marker, map, infoWindow, html);
              }
          });
          
             downloadUrl("wp-admin/admin-ajax.php?action=maponlineuser", function(data) {
                var xml = data.responseXML;
                var markers = xml.documentElement.getElementsByTagName("marker");
                for (var i = 0; i < markers.length; i++) {
                    var name = markers[i].getAttribute("name");
                    var address = markers[i].getAttribute("address");
                    var type = markers[i].getAttribute("type");
                    var point = new google.maps.LatLng(
                            parseFloat(markers[i].getAttribute("lat")),
                            parseFloat(markers[i].getAttribute("lng")));
                    var html = "<b>" + name + "</b> <br/>" + address;
                    var icon = customIcons[type] || {};
                    var marker = new google.maps.Marker({
                        map: maponline,
						center: new google.maps.LatLng(markers[i].getAttribute("lat"), markers[i].getAttribute("lng")),
                        position: point,
                        icon: image,
                    });
                    bindInfoWindow(marker, maponline, infoWindow, html);
                }
            });
            
            
            
            
        }

        function bindInfoWindow(marker, map, infoWindow, html) {
            google.maps.event.addListener(marker, 'click', function() {
                infoWindow.setContent(html);
                infoWindow.open(map, marker);
            });
        }

        function downloadUrl(url, callback) {
            var request = window.ActiveXObject ?
                    new ActiveXObject('Microsoft.XMLHTTP') :
                    new XMLHttpRequest;

            request.onreadystatechange = function() {
                if (request.readyState == 4) {
                    request.onreadystatechange = doNothing;
                    callback(request, request.status);
                }
            };

            request.open('GET', url, true);
            request.send(null);
        }

        function doNothing() {
        }
        
        function getLangLong() {
            
            var geocoder = new google.maps.Geocoder();
            var address = "dadar bridge";

            geocoder.geocode({'address': address}, function(results, status) {
                if (status == google.maps.GeocoderStatus.OK) {
                    var latitude = results[0].geometry.location.lat();
                        var longitude = results[0].geometry.location.lng();
                        alert('lat' + latitude + ' lng' + longitude);
                    }
            });
        }
        
        function showmap(showdiv,hidediv,id)
        {  
			
			if(id == 'guest')
			{
				jQuery("#"+id).css('color','#00F');
				jQuery("#member").css('color','#24890D');
			} 
			else
			{
				jQuery("#"+id).css('color','#00F');
				jQuery("#guest").css('color','#24890D');
			}
            jQuery("#"+showdiv).fadeIn('in');
            jQuery("#"+showdiv).attr('display','block');
            jQuery("#"+hidediv).fadeOut();
            jQuery("#"+hidediv).attr('display','none');
            
        }
      

        //]]>
 </script>
 <style>
   .gm-style img {
         max-width: 1100px !important;
     }
 </style>
 
 <div style="float:left; width:100%;">
    <a id="guest" href="javascript:void(0)" onclick="showmap('registeruser','onlineuser',this.id)">
    <img style="float:left;  margin-right:15px;" src="<?php echo plugins_url( "images/userRegister-icon35x35.png", __FILE__ ); ?>" /><h4 style="clear:none;">Usermap for register user </h4>    </a>
 </div>
 <div>
     <a id="member" href="javascript:void(0)" onclick="showmap('onlineuser','registeruser',this.id)">
     <img  style="float:left; margin-right:15px;" src="<?php echo plugins_url( "images/icon_liveevent.png", __FILE__ ); ?>" /><h4 style="clear:none;"> Usermap for Online user</h4>     </a>
 </div>
 
 <br /><br />

 <div id="registeruser">
     <h2> Usermap </h2>
    <div style="text-align:justify;width:100%;float:left;margin-bottom:30px">
    Usermap extension can be used to display your registered users on google map based on address values from registration form or the IP address. You can add this component to your website to display your registered users on google map, this way your visitors can have an idea of the density of users of your website from a particular region. 
    Plugin will be used to add IP address of the registered user in case you have not activated the address fields on your registration form.
    </div>
    <div style="clear:both"></div>
    <div id="map" style="width: 600px; height: 400px"></div>
 </div>   
 
 <div id="onlineuser">
     <h2> Online Usermap </h2> 
        <div style="text-align:justify;width:100%;float:left;margin-bottom:30px">
        Usermap extension can be used to display online registered users on google map based on address values from registration form or the IP address. You can add this component to your website to display your registered users on google map, this way your visitors can have an idea of the density of users of your website from a particular region. 
        Plugin will be used to add IP address of the registered user in case you have not activated the address fields on your registration form.
        </div> 
        <div style="clear:both"></div>	
        <div id="maponline" style="width: 600px; height: 400px"></div>    
 </div>  
 
    <script> jQuery('#onlineuser').fadeOut(); </script>
