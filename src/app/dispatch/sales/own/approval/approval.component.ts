import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css']
})
export class ApprovalComponent implements OnInit {

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
  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.getPendingSales();
  }
  getPendingSales(){
    this.service.get('dispatch/sales.php?type=getCheckedOrders').subscribe(response=>{
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


  ars =[];

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
    this.wholeProducts = this.selectedResult['sales_data']; 
    




     this.getCalculations();
    this.isView=true;


    
    this.ars =[];

    try {
      for (let i = 0; i < this.wholeProducts.length; i++) {
        const batch_no = this.wholeProducts[i].batch_no;
        
        this.service.get('dispatch/sales.php?type=getARNOfinished&batch_no=' + batch_no).subscribe(response=>{
        this.ars.push(response);
      });
    }
    } catch (error) {
      console.error('Error fetching ARNs:', error);
     }


     

    const myFunction = () => {
      this.sepretingars();
     };

     setTimeout(myFunction, 1000);
 
  }
  // async view(index){
  //   this.selectedResult=this.results[index];  
  //   this.getCalculations();
  //   this.selectedResult=this.results[index];
  //   this.gst_app=this.selectedResult['gst_app'];
  //   this.gst_type=this.selectedResult['gst_type'];
  //   this.bill_curr=this.selectedResult['bill_curr'];
  //   this.pay_mode=this.selectedResult['pay_mode'];
  //   this.po_no=this.selectedResult['po_no'];
  //   this.po_date=this.selectedResult['po_date'];
  //   this.final_total=this.selectedResult['final_total'];
  //   if(this.selectedResult['sales_data'].length>0){
  //   this.wholeProducts = JSON.parse(this.selectedResult['sales_data']); 
  //   }else{
  //     this.wholeProducts = [];
  //   }
  //    this.getCalculations();
  //   this.isView=true;


    
  //   this.ars =[];

  //   try {
  //     for (let i = 0; i < this.wholeProducts.length; i++) {
  //       const batch_no = this.wholeProducts[i].batch_no;
  //        const response = await this.service.get('dispatch/sales.php?type=getARNOfinished&batch_no=' + batch_no).toPromise();
  //       this.ars.push(response);
  //     }
  //   } catch (error) {
  //     console.error('Error fetching ARNs:', error);
  //    }


     

  //   const myFunction = () => {
  //     this.sepretingars();
  //    };

  //    setTimeout(myFunction, 1000);
 
  // }

  updateSales(status){

    let temp = {};
    temp['available_ars'] = this.available_ars;
    temp['Invoice_no'] = this.selectedResult['Invoice_no'];
    temp['issue_for'] = this.selectedResult['LglNm'];
    temp['client_no'] = this.selectedResult['client_code'];
    temp['final_total'] = this.selectedResult['final_total'];
    temp['final_total'] = this.selectedResult['final_total'];
    temp['wholeProducts'] = this.wholeProducts;
 
    console.log(temp);

    this.service.post('dispatch/sales.php?type=updateOrder&status=' + status + '&id=' + this.selectedResult['id'],JSON.stringify(temp)).subscribe(response => {
      if (response['status']) {
        alertify.success('sales Order updated Successfuly');
        this.isView = false;
        this.available_ars =[];
        this.getPendingSales();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });

    
  }


  available_ars =[];
  flattenedArray;

  sepretingars(){

    console.log("seprate");
    console.log(this.ars.length);
    console.log(this.ars);

    this.flattenedArray = [];
 
 
     
    this.ars.forEach((arr) => {
      console.log("1");
    
      arr.forEach((element) => {
        console.log("2");
        this.flattenedArray.push(element);
      });
    });


    console.log(this.flattenedArray);

    let desp_qty =0;

  //  console.log("1");
    for(let m = 0; m < this.wholeProducts.length; m ++){
      //console.log("2");
              const arr =  { ...this.flattenedArray[m] };
              desp_qty = this.wholeProducts[m].sale_qty;
              console.log(0 < this.flattenedArray.length && desp_qty > 0);
          for (let i = 0; i < this.flattenedArray.length && Number(desp_qty) > 0; i++) {

            console.log("jadugar");
            console.log(this.wholeProducts[m].product_code);
            console.log(this.flattenedArray[i].material_code);
            if(this.wholeProducts[m].product_code == this.flattenedArray[i].material_code && Number(desp_qty) > 0){

              const currentArno = { ...this.flattenedArray[i] };

              if (currentArno.balance_qty === 0) continue;
              // console.log(desp_qty +"<- desp   balance -> "+ currentArno.balance_qty);

               if (Number(desp_qty) >= Number(currentArno.balance_qty) ) {
                // console.log(desp_qty +"<- desp   balance -> "+ currentArno.balance_qty);
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


  btn1(){

    this.isView=false;
    this.available_ars =[];


  }


}
