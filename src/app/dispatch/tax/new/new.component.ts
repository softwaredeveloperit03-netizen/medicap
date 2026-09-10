import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {
  results;
  isView=false;
  selectedResult=[];
  qty='0';
  sale_rate=0
  gross_total = 0;
  gros_amt=0;
  net_amt=0;
  tax_amt=0;
  disc_total =0;
  tax_total = 0;
  net_total = 0;
  batches = [];
  productList=[];
  selectedGst=0;
  disc_per=0;
  selectrate;
  materials=[];
  data: any;
 
  constructor(private service:DataAccessService,private router:Router) { }

  ngOnInit(): void {
    this.getPendingInvoices();
  }

  getPendingInvoices(){
    this.service.get('dispatch/invoice.php?type=getPendingInvoices').subscribe(response=>{
      this.results=response;
     
    });
  }

  view(index){
    this.selectedResult=this.results[index];
    this.isView=true;
    this.materials=this.selectedResult['materials'];
    console.log(this.materials);
    this.selectrate=this.materials[index].sale_rate;
    console.log(this.selectrate);
  }

  keyPressNumbers(event) {
    var charCode = (event.which) ? event.which : event.keyCode;
    // Only Numbers 0-9
    if ((charCode < 48 || charCode > 57)) {
      event.preventDefault();
      return false;
    } else {
      return true;
    }
  }
  // this.disc_total=((this.gross_total * this.disc_per)/100);
  calculation(index){
   
    let tpgross= this.materials[index].gross_total;
    this.materials[index].gross_total= this.materials[index].qty*this.selectrate;
    let grosss= this.materials[index].gross_total;

    /////disc amount
    let discc= this.materials[index].disc_total;
    this.materials[index].disc_total=((this.materials[index].gross_total * this.materials[index].disc_per)/100);
    console.log('disctotal',this.materials[index].disc_total);
    ///taxable_amt
    let taxable = this.materials[index].gross_total - this.materials[index].disc_total;
    let taxTotal=this.materials[index].tax_total;
    this.materials[index].tax_total=((this.materials[index].gross_total* this.materials[index].gst_per)/100);
    console.log('gst', this.materials[index].gst_per);
    console.log('taxTotal', this.materials[index].tax_total);
    ////net amount
    let tpnet=this.materials[index].net_total;
    this.materials[index].net_total = taxable + this.materials[index].tax_total;
    console.log('netTotal', this.materials[index].net_total);
    ////table total

    // let To_gross= this.selectedResult['gross_total'];
    // console.log('dsds',To_gross);
    this.gros_amt = 0;
    for(let i=0;i<this.materials.length;i++){
      let material = this.materials[i];
      console.log('mat',material);
      this.gros_amt = +material['gross_total'];
      this.tax_amt = +material['tax_total'];
      console.log('final tax',+material['tax_total']);
      this.net_amt = +material['net_total'];
    }
     let selGross=  this.selectedResult['gross_total'];
      let seltax=  this.selectedResult['tax_total'];
      let selnet=  this.selectedResult['net_total'];
      this.selectedResult['gross_total'] = parseFloat(this.gros_amt + '').toFixed(2);
      this.selectedResult['tax_total'] = parseFloat(this.tax_amt + '').toFixed(2);
      this.selectedResult['net_total']=parseFloat(this.net_amt + '').toFixed(2);
   
  }
   
  saveInvoice(){
    
    this.data=this.selectedResult['sales_data']
      // temp['gross_total']=this.gros_amt;
      // temp['tax_total']=this.tax_amt;
      // temp['net_total']=this.net_amt;
    this.service.post('dispatch/invoice.php?type=saveInvoice', JSON.stringify(this.selectedResult)).subscribe(response => {
      if (response['status'] === 'success') {
        this.router.navigate(['/dispatch/tax']);
        alertify.success('Successfully send for Approval');
      } else {
        alertify.error(this.service.t('common.errorOccurred'));
      }
    });
  }

}
