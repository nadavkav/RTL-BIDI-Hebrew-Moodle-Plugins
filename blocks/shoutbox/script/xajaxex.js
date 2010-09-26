


				//***Please Read it,But Don't Try To Modify*//
	//********************************************************************************
	//File Name 	: AjaxClassEx.js									*
	//File Version 	: 1.0.0										*
	//File Last Build: 11/12/2007 15.57									*
	//			: for IE<=IE7,firefox,opera							*
	//Copyright		:2007-08 (c) BOOKS Cybertech Pvt. ltd,www.bookscybertech.com	*
	//**********Note :It is absolutely Open source Code,If this code works fine		*
	// It is written by Lr Bijaya,BOOKS Cybertech Pvt. Ltd,					*
	// and just give me the credit nothing else.Otherwise code					*
	// is written someone else.										*
	//		Example: 											*
	//				var returnData='received';						*
	//				var ax=new ExAjaxClass();						*
	//			function cb(serverResponse,ARType,AStatus)				*
	//			{											*
	//				if(AStatus==AjaxStatus.OK)						*
	//				{										*
	//				returnData+="\n\n"+serverResponse;					*
	//				}										*
	//				else //error								*
	//				{										*
	//				returnData="error no:" +AStatus+"\n\n"+serverResponse;	*
	//				}										*
	//				alert(returnData);							*
	//			}											*
	//			ax.AddCallBackHnadler(cb,AjaxResponseType.responseTEXT);		*
	//		      ax.sendToServer(AjaxMethodType.POST,'test.php3',"name=bijay");	*
	//														*
	//********************************************************************************


































//*******[Enum Ajax ReadyState]********
var AjaxReadyState=new Object();
    	AjaxReadyState.UNINITIALIZED = 0;
   	AjaxReadyState.LOADING = 1;
	AjaxReadyState.LOADED = 2;
    	AjaxReadyState.INTERACTIVE = 3;
   	AjaxReadyState.COMPLETE = 4;


//*******[Enum Ajax Status]********
var AjaxStatus=new Object();
      AjaxStatus.OK=200 ;
	AjaxStatus.Created =201 ;
	AjaxStatus.Accepted =202 ;
	AjaxStatus.No_Content=204 ; 
	AjaxStatus.Moved_Permanently=301; 
	AjaxStatus.Moved_Temporarily =302;

	AjaxStatus.Not_Modified =304;
	AjaxStatus.Bad_Request =400;
	AjaxStatus.Unauthorized=401; 
	AjaxStatus.Forbidden =403 ;
	AjaxStatus.Not_Found =404;
	AjaxStatus.Method_Not_Allowed =405;
	AjaxStatus.NotAcceptable =406;
	AjaxStatus.PreconditionFailed=407;
	AjaxStatus.RequestTimeout  =408;
	AjaxStatus.Conflict  =409;
	AjaxStatus.Gone=410;
	AjaxStatus.RequestEntityTooLarge  =413;
	AjaxStatus.RequestUriTooLong  =414;
	AjaxStatus.Unsupported_MediaType  =415;
	AjaxStatus.RequestedRangeNotSatisfiable=416;
	AjaxStatus.ExpectationFailed=417;

	AjaxStatus.Internal_Server_Error=500 ; 
	AjaxStatus.Not_Implemented =501 ;
	AjaxStatus.Bad_Gateway =502 ;
	AjaxStatus.Service_Unavailable=503 ; 

	AjaxStatus.Open_Mathod_Fail =600 ;
	AjaxStatus.Send_Mathod_Fail=601 ;


	AjaxStatus.Pequest_Pending=700 ;


	


//*******[Enum Ajax Response Type]********
var AjaxResponseType=new Object();
    AjaxResponseType.responseXML=1; //onseXML';
    AjaxResponseType.responseTEXT=2; //onseText';


//*******[Enum Ajax Method Type]********
var AjaxMethodType=new Object();
    AjaxMethodType.POST=1;
    AjaxMethodType.GET=2;
    AjaxMethodType.HEAD=3;
    AjaxMethodType.UNDEFINED=4;






//*******[ Ajax Class]********
function ExAjaxClass()
	{
		

		// Public Property
		this.request=null;
		this.response=null;
		this.callingMethod='';
		this.responseType=null ; //''
		this.AjaxCallbackFunction=null;
		this.methodType=null;
		this.loaded=2;

		//********[ADD AJAX CALLBACK HANDLER ]*********/
		this.AddCallBackHnadler=function(AjaxCallbackFunction,responsetype)
		{
			
			this.responseType='';
			switch(responsetype)
			{
				case AjaxResponseType.responseXML:
					this.responseType=AjaxResponseType.responseXML; //'responseXML'; // ;
					break;
				case AjaxResponseType.responseTEXT:
					this.responseType=AjaxResponseType.responseTEXT; //'responseText'; //;
					break;
			}
			
			this.AjaxCallbackFunction=AjaxCallbackFunction||null;
		};//AddCallBackHnadler




	/********* M E T H O D *********/
this.initialize=function()
{
	this.response=null;
	this.callingMethod='';
		
	if(!this.request)
	{
		if(window['XMLHttpRequest'])
		{
			/*IE7, Mozillas*/ 
			try
			{
				this.request=new XMLHttpRequest();
			}
			catch(e)
			{
				this.request=null;
			};
		}
		else if(window['ActiveXObject'])
		{
			/*IE<IE7*/
			var ajaxMSversions=[/*'Msxml2.DOMDocument.5.0', 'Msxml2.DOMDocument.4.0', 'Msxml2.DOMDocument.3.0', 'MSXML2.DOMDocument',*/ 'Msxml2.XMLHTTP', 'Microsoft.XMLHTTP'	];
			for(var v=0; v<ajaxMSversions.length; v++)
			{
				try
				{
					this.request=new ActiveXObject(ajaxMSversions[v]);
					 return this.request;
				}
				catch(e)
				{
					this.request=null;
				};
			}
		}
		else if(window['createRequest'])
		{ 
			try
			{
				this.request=window.createRequest();
			}
			catch(e)
			{
				this.request=null;
			}; 
		}
		else
		{
			alert('XMLHTTP not enabled. Impossible to proceed.'
		);
	}
	};
return this.request;
}



	this.sendToServer=function(methodtype,uri,arguments)
	{	
		
		if(this.loaded!=AjaxReadyState.LOADED)
		{
			
			if(typeof(this.AjaxCallbackFunction)=="function")
			{		
				this.response="Already one request is pending.";
			 	this.AjaxCallbackFunction(this.response,this.responseType,AjaxStatus.Pequest_Pending);
			}
			return ;
		}
			
		
		if(methodtype!=undefined)
		{	
			
			
			if(!uri || !this.initialize()){return false;};
		
			arguments=arguments||'';

			
			switch(methodtype)
			{
				case AjaxMethodType.POST:
					this.methodType=AjaxMethodType.POST;
					this.callingMethod='POST';
					arguments=unescape(arguments);
					
					try
					{
						this.request.open('POST', uri, true);
						this.request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
					}
					catch(e)
					{if(typeof(this.AjaxCallbackFunction)=="function")
						{		
							this.AjaxCallbackFunction(e.message,this.responseType,e.number);
							delete this.request;
							return;
						}
					}

					
					break;
				case AjaxMethodType.GET:
					this.methodType=AjaxMethodType.GET;
					this.callingMethod='GET';
					arguments=arguments.replace(/\?/, '');
					arguments=unescape(arguments);
					

					try
					{
						this.request.open('GET', (uri+'?'+arguments), true);
						this.request.setRequestHeader('Content-Type', 'text/xml');
					}
					catch(e)
					{if(typeof(this.AjaxCallbackFunction)=="function")
						{		
							this.AjaxCallbackFunction(e.message,this.responseType,e.number);
							delete this.request;
							return;
						}
					}



					
					arguments=null;
					break;
				case AjaxMethodType.HEAD:
					this.methodType=AjaxMethodType.HEAD;
					this.callingMethod='HEAD';
					


					try
					{
						this.request.open('HEAD', uri, true);
						this.request.setRequestHeader('Content-Type', 'text/xml');
					}
					catch(e)
					{if(typeof(this.AjaxCallbackFunction)=="function")
						{		
							this.AjaxCallbackFunction(e.message,this.responseType,e.number);
							delete this.request;
							return;
						}
					}

					
					arguments=(arguments||null);
					break;
			}
			
			
			
			try
			{
				this.request.onreadystatechange=this.AjaxCallback(this);
				this.request.send(arguments);
			}
			catch(e)
			{if(typeof(this.AjaxCallbackFunction)=="function")
				{		
				this.AjaxCallbackFunction(e.message,this.responseType,e.number);
				delete this.request;
				return;
				}
			}


	      	
		}
		else
		{
			return false;
		}
		

	};//doSendRequest

	/********* [ METHOD Error ] *********/
	this.error=function(statusError)
	{
		if(statusError)
		{
			this.response=(this.request && this.request.status)? 'Ajax Error: '+this.request.status+': '+this.request.statusText: 'Ajax Error: Requested document may be temporarily unavailable';
			//alert(this.response);
			return this.response;
		}
		else
		{
			return false;
		}
	}


	/********* M E T H O D *********/
	this.AjaxCallback=function(ajaxInstance)
	{
		return function()
		{

		
		//currying
		if(ajaxInstance.request.readyState==AjaxReadyState.COMPLETE || ajaxInstance.request.readyState=='complete')
		{
			ajaxInstance.loaded=AjaxReadyState.LOADED;
			if(ajaxInstance.request.status==AjaxStatus.OK)
			{
				
				var _callingMethod,_responseType;
 				_callingMethod='';
				_responseType='';
				switch(ajaxInstance.methodType)
				{
					case AjaxMethodType.POST:
						_callingMethod="POST";
						break;
					case AjaxMethodType.GET:
						_callingMethod="GET";
						break;
					case AjaxMethodType.HEAD:
						_callingMethod="HEAD";
						break;
				}

				
				switch(ajaxInstance.responseType)
				{
					case AjaxResponseType.responseXML:
						_responseType='responseXML'; 
						break;
					case AjaxResponseType.responseTEXT:
						_responseType='responseText'; 
						break;
				}

				
				ajaxInstance.response=(_callingMethod=='GET' || _callingMethod=='POST')?ajaxInstance.request[_responseType]:
				(_callingMethod=='HEAD')?ajaxInstance.request.getAllResponseHeaders():false;
				
				if(typeof(ajaxInstance.AjaxCallbackFunction)=="function")
				{		
					
					ajaxInstance.AjaxCallbackFunction(ajaxInstance.response,ajaxInstance.responseType,ajaxInstance.request.status);
					// delete this.request;
					// alert("hetre");
					return;
				}
					return ajaxInstance.response;
			}
			else
			{
				ajaxInstance.AjaxCallbackFunction(ajaxInstance.response,ajaxInstance.responseType,ajaxInstance.request.status);
				//delete this.request;
				//return;
				//return ajaxInstance.error(1);
			}
		}
		else
		{
			if(ajaxInstance.request.readyState==AjaxReadyState.LOADING)
				ajaxInstance.loaded=AjaxReadyState.LOADING;
			
			if(ajaxInstance.request.readyState==AjaxReadyState.LOADED)
				ajaxInstance.loaded=AjaxReadyState.LOADED;


			//alert(ajaxInstance.request.readyState);
			//return ajaxInstance.AjaxCallbackFunction(ajaxInstance.response,ajaxInstance.methodType,ajaxInstance.request.status);
			// return ajaxInstance.error(0);
		};
		}//currying over
	}


}




/*

	var returnData='received';
	var ax=new ExAjaxClass();
	function cb(serverResponse,ARType,AStatus)
	{
		
		if(AStatus==AjaxStatus.OK)
		{
			returnData+="\n\n"+serverResponse;
		}
		else //error
		{
			returnData="error no:" +AStatus+"\n\n"+serverResponse;
		}
		
		alert(returnData);
	}
	
	ax.AddCallBackHnadler(cb,AjaxResponseType.responseTEXT);
	//ax.post('http://etest.booksinstitute.com/respone.PHP3');
	 ax.sendToServer(AjaxMethodType.POST,'http://etest.booksinstitute.com/respone.PHP3',"DDD");
	//alert("send Next");
	 ax.sendToServer(AjaxMethodType.POST,'http://etest.booksinstitute.com/respone.PHP3',"DDD");
	
	//alert("send Next Completed");

*/







