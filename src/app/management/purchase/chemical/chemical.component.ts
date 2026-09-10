
  import { DatePipe } from '@angular/common';
  import { Component, OnInit } from '@angular/core';
  import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-chemical',
  templateUrl: './chemical.component.html',
  styleUrls: ['./chemical.component.css'],
  providers:[DatePipe]
 
})
export class ChemicalComponent implements OnInit {
    today='';
    from_date='';
    to_date='';
    results;
    trade_name;
    selectedResult=[];
    isView=false;

    po_status="";
    orders;
    disc_amt=0;
    total=0;
    selectedOrder;
    remark = '';
    selectedMaterial=[];
    terms_condition:any=[];
    additional_terms:any=[];
    status='';
    vendor_no='';
    departments;
    item = [];
    vendors;
    selectedBill: any;
    selectedShip: any;
    totalAmount: number;
  
    constructor(private service:DataAccessService ,private datePipe:DatePipe) {     
       this.from_date=this.datePipe.transform(Date.now(),'yyyy-MM-01');   
      this.to_date=this.datePipe.transform(Date.now(),'yyyy-MM-dd');   
      this.today=this.datePipe.transform(Date.now(),'yyyy-MM-dd');   }
  
    ngOnInit()  {
      this.getdata();
    }
      getdata() {
   
        this.service.get('purchase/po/raw.php?type=getChemicallog').subscribe(response => {
          this.results = response;
        
        });
      }
   
  
    getBillCompany(val){
      console.log(val);
      this.service.get('master/company.php?type=getCompanyByCode&company_code='+val).subscribe(response => {
        this.selectedBill=response[0];
      })
    }
    getShipCompany(val){
      console.log(val);
      this.service.get('master/company.php?type=getCompanyByCode&company_code='+val).subscribe(response => {
        this.selectedShip=response[0];
      })
    }
  
    view(index){  
      console.log(this.results);
      this.selectedOrder = this.results[index];
      this.selectedOrder.gstData = JSON.parse(this.selectedOrder.gstSplitData);
      this.disc_amt=this.selectedOrder['net_total']*1*this.selectedOrder['discount']/100;
      this.total=this.selectedOrder['net_total']*1-this.disc_amt*1;
      this.selectedOrder = this.results[index];
      console.log(this.selectedOrder);
       this.additional_terms =  this.selectedOrder['additional_term'];
      this.getBillCompany(this.selectedOrder['billcompany_code']);
      this.getShipCompany(this.selectedOrder['shipcompany_code']);
      this.terms_condition= this.selectedOrder['terms_conditions'];
       this.additional_terms = this.selectedOrder['additional_term'];
      this.disc_amt=this.selectedOrder['net_total']*1*this.selectedOrder['discount']/100;
      this.total=this.selectedOrder['net_total']*1-this.disc_amt*1;
      this.isView = true;
      this.totalAmount = Number(this.selectedOrder['shipping_handling'] )+ this.selectedOrder['shipping_handling'] *Number(this.selectedOrder['shipping_gst'])/100;
    }
    download(){
      this.service.open('headquarter.php?type=getChemicalPurchaseOrders&from_date='+this.from_date+'&to_date='+this.to_date)
    }
  
  }
  