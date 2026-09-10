import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-rawapproval',
  templateUrl: './rawapproval.component.html',
  styleUrls: ['./rawapproval.component.css']
})
export class RawapprovalComponent implements OnInit {

  results;
  isView=false;
  selectedResult=[];

  calculated = {
    gross: 0,
    disc: 0,
    taxable: 0,
    other: 0,
    round: 0,
    net: 0,
  };
  gst_app: any;
  gst_type: any;
  bill_curr: any;
  pay_mode: any;
  po_no: any;
  po_date: any;
  final_total: any;
  wholeProducts: any;
  flattenedArray;
  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.getPendingSales();
  }
  getPendingSales(){
    this.service.get('dispatch/sales.php?type=getCheckedOrdersRaw').subscribe(response=>{
      this.results=response;
    });
  }

  getCalculations(){
    let data = this.selectedResult['materials'];
    this.calculated={
      gross: 0,
      disc: 0,
      taxable: 0,
      other: 0,
      round: 0,
      net: 0,
    }
    data.map(res=>{
      console.log(res.disc_total);
      this.calculated.gross += parseFloat(res.gross_total);
      // console.log(res);
      this.calculated.disc += parseFloat(res.disc_total);
      this.calculated.taxable += parseFloat(res.taxable);
      this.calculated.other += parseFloat(res.other);
      this.calculated.net += parseFloat(res.net_total);
    });
    console.log(this.calculated); 
  }
  ars;
  view(index){
    this.selectedResult=this.results[index];  
    this.getCalculations();
    this.selectedResult=this.results[index];
    this.gst_app=this.selectedResult['gst_app'];
    this.gst_type=this.selectedResult['gst_type'];
    this.bill_curr=this.selectedResult['bill_curr'];
    this.pay_mode=this.selectedResult['pay_mode'];
    this.po_no=this.selectedResult['po_no'];
    this.po_date=this.selectedResult['po_date'];
    this.final_total=this.selectedResult['final_total'];
    if(this.selectedResult['sales_data'].length>0){
    this.wholeProducts = JSON.parse(this.selectedResult['sales_data']); 
    }else{
      this.wholeProducts = [];
    }
    //console.log(this.selectedResult['sales_data']);
    this.getCalculations();
    this.isView=true;



this.ars =[];
    for(let i = 0; i< this.wholeProducts.length;i++){

      let material_code = this.wholeProducts[i].material_code;

      console.log(material_code);
      this.service.get('dispatch/sales.php?type=getARNO&material_code='+material_code).subscribe(response=>{
       
        this.ars.push(response);
         
      });

    }



    const myFunction = () => {
      this.sepretingars();
      
      
     };
  
  
  setTimeout(myFunction, 1000);
 



  }

  updateSales(status){

    let temp = {};
    temp['available_ars'] = this.available_ars;

    this.service.post('dispatch/sales.php?type=updateOrderraw&status=' + status + '&id=' + this.selectedResult['id'],JSON.stringify(temp)).subscribe(response => {
      if (response['status']) {
        alertify.success('sales Order updated Successfuly');
        this.isView = false;
        this.getPendingSales();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });


  }

  available_ars =[];

  sepretingars(){
    this.flattenedArray = [];


    console.log(this.ars);
    for(let i =0; i<this.ars.length;i++){

      console.log("1");
      const arr = this.ars[i];

      for(let x =0; x< arr.length;x++){
        console.log("2");
        this.flattenedArray.push(arr[x]);

      }
    }
    console.log(this.flattenedArray);






    let desp_qty =0;

    for(let m = 0; m < this.wholeProducts.length; m ++){

              const arr =  { ...this.flattenedArray[m] };
              desp_qty = this.wholeProducts[m].sale_qty;
              
          for (let i = 0; i < this.flattenedArray.length && desp_qty > 0; i++) {

            if(this.wholeProducts[m].material_code == this.flattenedArray[i].material_code && Number(desp_qty) > 0){


              const currentArno = { ...this.flattenedArray[i] };

              if (currentArno.balance_qty === 0) continue;
              console.log(desp_qty +"<- desp   balance -> "+ currentArno.balance_qty);

               if (Number(desp_qty) >= Number(currentArno.balance_qty) ) {
                console.log(desp_qty +"<- desp   balance -> "+ currentArno.balance_qty);
                console.log("BYE");
                if(Number(currentArno.balance_qty) > 0){
                  this.available_ars.push(currentArno);
                  desp_qty -= currentArno.balance_qty;
                  currentArno.despensedQty =  currentArno.balance_qty;
                  currentArno.qty = 0;

                }else{
                  continue;
                }
              
              } else {
                if(Number(currentArno.balance_qty) > 0){
                this.available_ars.push(currentArno);
                  currentArno.qty -= Number(desp_qty);
                  currentArno.despensedQty =  Number(desp_qty);
                  desp_qty = 0;
                  console.log("HI");

                }else{
                  continue;
                }
              }

            }
            
              
          }


        }

        console.log(this.available_ars);

  }


}
