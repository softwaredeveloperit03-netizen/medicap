import { Component, OnInit } from '@angular/core';
import { FormArray, FormBuilder, FormControl, FormGroup, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-rawbulk',
  templateUrl: './rawbulk.component.html',
  styleUrls: ['./rawbulk.component.css']
})
export class RawbulkComponent implements OnInit {

  material_code='';
  clients;
  selectedClient = [];
  branches = [];
  batches =[];
  materials=[];
  client_code='';
  branch='';
  batch='';
  material_type ='Raw Material';
  available_qty= 0;
  requiredQty = 0;
  gross_total = 0;
  other_charge_amt = 0;
  disc_total = 0;
  taxable = 0;
  tax_total = 0
  net_total = 0;
  sgst_amt = 0;
  igst_amt= 0 ;
  cgst_amt= 0;
  RndOffAmt = 0;
  TotInvVal = 0;
  TotInvValFc = 0;
  gst_amt = 0;
  Igst_amt = 0;
  Cgst_amt = 0;
  product_type='API';
  Sgst_amt = 0;
  freight=0;
  selectedBranch = [];
  selectedBatch = [];
  clientsData=[];
  clientsData1=[];
  Clientbranch;
  isShow=false;
  lgLnm;
  clientCode;
  products;
  productArray;
  qty = 0;
  free_qty = 0;
  rate = 0;
  curr_rate = 0;
  gst;
  gsts;
  disc_percent = 0;
  sub_total = 0;
  other = 0;
  other_charges = 0;
  sale_qty= 0;
  order_qty= 0;
  totalRequiredQty= 0;
  balance_qty= 0;
  singleval= 0;
  bill_curr = 'INR';
  value_in_inr=0;
  singleProduct=[];
  wholeProducts=[];
  no_of_container=0;
  final=0;
  final_total=0;
  val_in_inr=0;
  PRASAD;
  fg_sub_materials = [];
  gstList=[];
  dosage_form;
  dosage_type;
  packing_style;
  hsn;

  constructor(private service:DataAccessService,private router:Router) { 
  }

  ngOnInit(): void {
    this.getClients();
 
   this.getGST();
   this.getSubMaterials();
 
  }

  calc(){
    this.gross_total = this.rate * this.sale_qty;
  }

  findContainers(val){
    this.no_of_container = this.requiredQty / parseFloat(val);
    console.log(this.requiredQty, val, this.no_of_container);
  }
  addSingle(form){
    if(this.singleval==0){
      this.singleProduct.push(form.value);
      this.balance_qty = this.requiredQty - this.sale_qty;
      this.singleval++;
      form.reset();
    }
    else if(this.balance_qty<this.sale_qty){
      alertify.error('Balance qty should be greater than sale qty!')
    }
    else{
      this.singleProduct.push(form.value);
      this.balance_qty -=  this.sale_qty;
      form.reset();
    }
  }
  
  deleteSingle(val){
    this.singleProduct.splice(val,1);
  }
  
  addWhole(form){
    form.value.singleProduct = this.singleProduct;
     let grossval = 0; 
    let totalval = 0;
     let gross = this.singleProduct.map(res=>grossval+= res.gross_total);
    let test = this.singleProduct.map(res=>totalval+= res.net_total);
    let sale_qty = this.singleProduct[0].sale_qty;
     form.value.gross_total = grossval;
    form.value.net_total = totalval;
    form.value.material_type = 'Raw Material';
    form.value.material_name = this.material_name1;
    form.value.material_code = this.material_code1;
    form.value.sale_qty = sale_qty;
    form.value.Igst_amt = this.singleProduct[0].Igst_amt;
    form.value.gross_total = this.singleProduct[0].gross_total;
    form.value.gst = this.singleProduct[0].gst;
    form.value.gst_amt = this.singleProduct[0].gst_amt;
    form.value.net_total = this.singleProduct[0].net_total;
    this.final += totalval;

    this.wholeProducts.push(form.value);
    console.log(this.wholeProducts);
    this.singleProduct=[];
    this.singleval=0;
    this.balance_qty=0;
    this.sale_qty=0;
    form.reset();
  }

  deleteWhole(val){
    this.wholeProducts.splice(val, 1);
  }


  findValue(){
    this.value_in_inr = this.curr_rate * this.rate;
  }


  getGST(){
    this.service.get('common.php?type=getGST').subscribe(response=>{
      this.gsts=response;
    });
  }


 



 
  addProduct(data){
    console.log(this.order_qty , this.totalRequiredQty)
 
    let test = this.totalRequiredQty + this.requiredQty;
 
    data.value.requiredQty = this.requiredQty;
    data.value.product_code = this.material_code;
    data.value.qty = this.qty;
    data.value.gross_total= this.gross_total;
    data.value.disc_total = this.disc_total;
    data.value.taxable = this.taxable;
    data.value.net_total= this.net_total;
    data.value.gst = parseFloat(data.value.gst)*2;
    this.materials.push(data.value);
    data.reset();
  }

  getClients(){
    this.service.get('common.php?type=getClients').subscribe(response=>{
      this.clients = response;
    });
  }

 

  getBranch(index){
    index = index - 1;
    if (index !== -1) {
      this.clientsData=this.clients[index];
      this.lgLnm=this.clientsData['LglNm']
      this.clientCode=this.clientsData['client_code']
      this.getClientBranches(this.clientCode);
      this.branches = this.clients[index].branch;
    }
  }

  getClientBranches(clientCode){
    this.service.get('common.php?type=getClientBranches&client_code='+ clientCode).subscribe(response=>{
      this.Clientbranch = response;
    });
  }


  getBranch1(index){
    index = index - 1;
    if (index !== -1) {
      this.clientsData1=this.clients[index];
       this.branches = this.clients[index].branch;
    }
  }

 

  selectBranch(index) {
    index = index - 1;
    if (index !== -1) {
      this.selectedBranch = this.branches[index];
    }
  }

  selectBatch(index) {
    index = index - 1;
    if (index !== -1) {
      this.selectedBatch = this.batches[index];
    }
  }

  
 

 

  calc3(){
    console.log(this.gst);
    let ggst = parseFloat(this.gst.split(' ')[0]);
    this.Cgst_amt = (this.gross_total * ggst * (1/100))/2;
    this.Sgst_amt = (this.gross_total * ggst * (1/100))/2;
    // ggst = ggst * 2;
    this.Igst_amt = this.gross_total * ggst * (1/100);
    // this.Igst_amt = this.gross_total +this.Cgst_amt+this.Sgst_amt;
    this.gst_amt = this.gross_total* ggst * (1/100);
    this.net_total = this.gross_total + this.gst_amt;
    let data3= this.net_total;
    this.final_total  = parseFloat((data3).toFixed(2));
  }

  calc4(){
    let data1 = this.final + this.other_charges; 
    this.final_total  = parseFloat((data1).toFixed(2));
  }

  calc5(){
    let data = this.final_total + this.freight;
    this.final_total = parseFloat((data).toFixed(2));
  }

   

  saveData(data){
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }

    let temp = data.value;
    temp['client_code']=this.client_code;
    temp['disc_percent']=this.disc_percent;
    temp['val_in_inr']=this.val_in_inr;
     temp['other']=this.other;
    temp['sub_total']=this.sub_total;
    temp['final_total']=this.final_total;
    temp['sales_data']=this.wholeProducts;
    temp['materials'] = this.materials;

    console.log(temp);
    temp['gst_type'] = this.clientsData['gst_type'];
    temp['branch']=this.branch;
    temp['company'] = this.clientsData['LglNm'];
    temp['gross_total']=this.gross_total;
    temp['disc_total']=this.disc_total;
    temp['taxable'] = this.taxable;
    temp['net_total']=this.net_total;
    temp['SgstVal']=this.sgst_amt;
    temp['CgstVal']=this.cgst_amt;
    temp['IgstVal']=this.igst_amt;
    temp['other_charges']=this.other_charges;
    temp['tax_total']=this.tax_total;
    temp['RndOffAmt']=this.RndOffAmt;
    temp['TotInvVal']=this.TotInvVal;
    temp['TotInvValFc']=this.TotInvValFc;
    temp['requiredQty']=this.requiredQty;
    temp.client_code = this.client_code;
    
    this.service.post('dispatch/sales.php?type=saveBulkOrder1',JSON.stringify(temp)).subscribe(response=>{
      if(response['status']=='success'){
        alertify.success('save succesfuly');
        this.router.navigate(['/dispatch/sales']);
      }else{
        alertify.error('Some Error Occured');
      }
    });



  }

  materialname;
  getSubMaterials(){

     
     this.service.get('production/product.php?type=materialnamebytype&material_type=' + this.material_type).subscribe(response => {
      this.materialname = response;
     });
    
  }


  getdetails(index){

    // this.selected


  }



 
  getavaliable() {
   
    this.available_qty = this.PRASAD;
    
    
  }
  material_code1;
  material_name1;
  selectedProduct=[];
  getProductCode(index) {
    this.selectedProduct = this.materialname[index-1];
    console.log(this.selectedProduct);
    this.material_code = this.selectedProduct['material_code'];
     this.material_name1 = this.selectedProduct['material_name'];
    this.material_code1 = this.selectedProduct['material_code'];
    this.hsn = this.selectedProduct['hsn'];


    this.service.get('production/product.php?type=getavaliablestock&material_code=' + this.material_code).subscribe(response => {
      this.PRASAD = response;
      this.available_qty = this.PRASAD;

    });

    setTimeout(() => {
      this.getavaliable();
    }, 1000);
   }
   


}
