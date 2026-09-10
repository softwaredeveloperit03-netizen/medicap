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
  client_code='';
  branch='';
  results;
  mat;
  purchase_qty=0;
  gross_total=0;
  sale_rate=0;
  disc_per=0;
  disc_total=0;
  tax_total=0;
  net_total=0;
  batches = [];
  productList=[];
  selectedGst=0;
  selectedProduct='';
  selectBranch=[];
  grossTotal=0;
  taxTotal=0;
  netTotal=0;
  discTotal=0
  constructor(private service:DataAccessService,private router:Router) { }

  ngOnInit(): void {
    this.getClients();
    this.getMaterials();
  }

  getClients(){
    this.service.get('common.php?type=getClients').subscribe(response=>{
      this.results=response;
    });
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

  getBranch(index){
    index = index - 1;
    if (index !== -1) {
      this.selectBranch = this.results[index].branch;
      console.log('batch',this.selectBranch);
    } else {
      this.batches = [];
    }
  }

  add(data){
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    let temp=data.value;
    temp['gst_per']=this.selectedGst;
    temp['product_code']=this.selectedProduct;
    this.productList[this.productList.length] = data.value;
     data.resetForm();

      ///total total 
      this.grossTotal += +temp['gross_total'];
      this.discTotal += +temp['disc_total'];
      this.taxTotal += +temp['tax_total'];
      this.netTotal += +temp['net_total'];
  }


  getMaterials(){
    this.service.get('dispatch/sales.php?type=getMaterials').subscribe(response=>{
      this.mat=response;
    });
  }

  calculation(){
      this.gross_total=this.sale_rate*this.purchase_qty;
      this.disc_total=((this.gross_total * this.disc_per)/100);
      let taxable_amt= this.gross_total-this.disc_total;
      this.tax_total=((this.gross_total * this.selectedGst)/100);
      this.net_total=taxable_amt + this.tax_total;
   

  }

  getBatches(index) {
    index = index - 1;
    if (index !== -1) {
      this.batches = this.mat[index].batches;
      this.selectedGst=this.mat[index].gst;
      this.selectedProduct=this.mat[index].product_code;
      console.log('code',this.selectedProduct);
    } else {
      this.batches = [];
    }
  }

  saveData(data){
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    let temp=data.value;
    temp['materials']=this.productList;
    temp['gross_total']=this.gross_total;
    temp['disc_total']=this.disc_total;
    temp['tax_total']=this.tax_total;
    temp['net_total']=this.net_total;
    temp['client_code']=this.client_code;
    temp['branch']=this.branch;
    this.service.post('dispatch/sales.php?type=saveThirdParty',JSON.stringify(temp)).subscribe(response=>{
      if(response['status']=='success'){
        alertify.success('data save succesfuly');
        this.router.navigate(['/sales/third']);
      }else{
        alertify.error('Some Error Occured');
      }
    });
  }
}
