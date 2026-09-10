import { Component, OnInit } from '@angular/core';
import { FormArray, FormBuilder, FormControl, FormGroup, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-bulk',
  templateUrl: './bulk.component.html',
  styleUrls: ['./bulk.component.css']
})
export class BulkComponent implements OnInit {
  product_code='';
  clients;
  selectedClient = [];
  branches = [];
  batches =[];
  materials=[];
  client_code='';
  branch='';
  batch='';
  material_type ='';
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
  process_types;
  fg_sub_materials = [];
  gstList=[];
  dosage_form;
  dosage_type;
  packing_style;
  gst_app;

  conuntry_of_origin: string;
  exporters_reference: string;
  shipping_mark: string;
  packing: string;
  pre_carriage_by: string;
  place_of_receipt_by: string;
  country_of_origin_of_goods: string;
  country_of_final_destination: string;
  vessel_or_voyage_no: string;
  port_of_loading: string;
  port_of_discharge: string;
  final_destination: string;
  gst_applicable: string;
  lut_number: string;
  domestic_or_export:string;
  isEdit: boolean = false
  isTerm: boolean = false
  terms: any;
  isAddterm:any;
  isTermedit:any;
  Addterms;
  

  constructor(private service:DataAccessService,private router:Router) { 
  }

  ngOnInit(): void {
    this.getClients();
    // this.getMaterials();
   // this.getBatch();
   this.getStepsAndStages();

   this.getGST();
   this.getTerm();
   this.getAddterm();
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
    else if(this.balance_qty < this.sale_qty){
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
    // form.value.singleProduct = this.singleProduct;
    let batch_no = this.singleProduct[0].batch_no;
    let sale_qty = this.singleProduct[0].sale_qty;
    let gst_amt = this.singleProduct[0].gst_amt;
    let gst = this.singleProduct[0].gst;
    let grossval = 0;
    let totalval = 0;
    let temp = form.value;



    for(let i = 0  ; i< this.singleProduct.length;i++){

      grossval+= this.singleProduct[i].gross_total
      totalval+= this.singleProduct[i].net_total

    }
    
    let gross = grossval // this.singleProduct.map(res=>grossval+= res.gross_total);
    let test = totalval //this.singleProduct.map(res=>totalval+= res.net_total);

    temp['batch_no'] = batch_no;
    temp['gross_total'] = grossval;
    temp['net_total'] = totalval;
    temp['sale_qty'] = sale_qty;
    temp['gst'] = gst;
    temp['gst_amt'] = gst_amt;
    this.final += totalval;
    this.wholeProducts.push(temp);
    this.singleProduct=[];
    this.singleval=0;
    this.balance_qty=0;
    this.sale_qty=0;
    form.reset();


    console.log(this.wholeProducts);
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

  getQty(val){
    console.log(this.batches[val-1]);
    this.available_qty = this.batches[val-1]['qty'];
  }

  getBatch(index){
    // index = index-1;
    // if(index!=index-1){
      console.log(this.products[index-1])
      this.batches = this.products[index-1]['grns'];
      this.product_code = this.products[index-1]['product_code'];
      this.qty = this.products[index-1]['balance_qty'];
    // }
  }

  getProductsByDosage(value) {
    this.service.get('dispatch/sales.php?type=getProducts&product_type=' + this.product_type).subscribe(response => {
      this.products = response;
    });
  }



  addProduct(data){
    let test = this.totalRequiredQty + this.requiredQty;
    data.value.requiredQty = this.requiredQty;
    data.value.product_code = this.product_code;
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
      this.lgLnm=this.clientsData1['LglNm']
      this.clientCode=this.clientsData1['client_code']
      this.getClientBranches(this.clientCode);
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

   checkAddress(value) {
    if (value.target.checked){
      this.clientsData1['LglNm']=this.lgLnm;
      this.clientsData1['Addr1']=this.clientsData['Addr1'];
      this.clientsData1['Addr2']=this.clientsData['Addr2'];
      this.clientsData1['gst_no']=this.clientsData['gst_no'];
      this.clientsData1['city']=this.clientsData['city'];
      this.clientsData1['state_name']=this.clientsData['state_name'];
      this.clientsData1['state_code']=this.clientsData['state_code'];
      this.clientsData1['gst_type']=this.clientsData['gst_type'];
      this.clientsData1['Pin']=this.clientsData['Pin'];
    } else {
      this.clientsData1['LglNm']='';
      this.clientsData1['Addr1']='';
      this.clientsData1['Addr2']='';
      this.clientsData1['gst_no']='';
      this.clientsData1['city']='';
      this.clientsData1['state_name']='';
      this.clientsData1['state_code']='';
      this.clientsData1['gst_type']='';
      this.clientsData1['Pin']='';
    }
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

    let terms = [];
    for (let i = 0; i < this.terms.length; i++) {
      let term = this.terms[i];
      if (term['selected']) {
        terms[terms.length] = term;
      }
    }

    let temp = data.value;
    temp['client_code']=this.client_code;
    temp['disc_percent']=this.disc_percent;
    temp['val_in_inr']=this.val_in_inr;
    temp['disc_total']=this.disc_total;
    temp['other']=this.other;
    temp['sub_total']=this.sub_total;
    temp['final_total']=this.final_total;
    temp['sales_data']=this.wholeProducts;
    temp['materials'] = this.materials;
  
    temp['gst_type'] = this.clientsData['gst_type'];
    temp['branch']=this.branch;
    temp['materials'] = this.products;
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
    temp['terms_conditions'] = terms;
    temp['add_term'] = this.Addterms;
    temp.client_code = this.client_code;

 
     
    console.log(temp);

    this.service.post('dispatch/sales.php?type=saveBulkOrder',JSON.stringify(temp)).subscribe(response=>{
      if(response['status']=='success'){
        alertify.success('Bulk Material save succesfuly');
        this.router.navigate(['/dispatch/sales']);
      }else{
        alertify.error('Some Error Occured');
      }
    });
     
  }

  saveTerm(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    } 
    this.service.post('master/terms.php?type=saveTerms', JSON.stringify(data.value)).subscribe(response => {
      if (response['status'] == 'success') {
        this.getTerm();
        this.isTerm=false;
        alert('Record Inserted Successfully');
        data.resetForm();
      } else {
        alert('Please try Again');
      }
    });
  }

  getTerm(){
    this.service.get('master/terms.php?type=getTerms').subscribe(response=>{
      this.terms=response;
    });
  }

  delTerm(id){
    this.service.get('master/terms.php?type=deleteTerm&id='+id).subscribe(response => {
      this.getTerm();
      if(response['status'] == 'success') {
        this.isEdit = false;
        alertify.success('Term Deleted Successfully');
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

  // For Adding the Term

  saveAddterm(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    this.service.post('master/terms.php?type=saveAddterms', JSON.stringify(data.value)).subscribe(response => {
      if (response['status'] == 'success') {
        this.getAddterm();
        this.isTerm = false;
        alertify.success('Record Inserted Successfully');
        data.resetForm();
      } else {
        alertify.error('Please try Again');
      }
    });
  }

  delAddterm(id) {
    this.service.get('master/terms.php?type=deleteAddterm&id=' + id).subscribe(response => {
      this.getAddterm();
      if (response['status'] == 'success') {
        this.isTermedit = false;
        alertify.success('Term Deleted Successfully');
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

  getAddterm() {
    this.service.get('master/terms.php?type=getAddterms').subscribe(response => {
      this.Addterms = response;
    });
  }

  // For Adding the Term

  materialname;
  getSubMaterials(val){

    if(val == 'Raw Material'){
    console.log("hii"+val);
    this.service.get('production/product.php?type=materialnamebytype&material_type=' + this.material_type).subscribe(response => {
      this.materialname = response;
     });
    }
  }


  getdetails(index){

    // this.selected


  }



  getProductsByDosageForm(type) {
    this.service.get('account/sales.php?type=getProductsByDosageForm&product_type=' + type).subscribe(response => {
      this.products = response;
    });
  }
  getStepsAndStages() {
    this.service.get('master/product.php?type=getBrandProductsLog').subscribe(response => {
      this.process_types = response;

    });
  }
  selectedProduct=[];
  PRASAD;
  product_name;

  getProductCode(index) {
    this.selectedProduct = this.process_types[index-1];
     this.product_code = this.selectedProduct['product_code'];
     this.product_name = this.selectedProduct['product_name'];
     console.log(this.product_code)

    
    this.service.get('account/sales.php?type=getavaliablestockfinished&product_code=' + this.product_code).subscribe(response => {
      this.PRASAD = response;
    });

  
  }

  Batch;
  batch_no;
  batchNo(index) {

    this.Batch = this.PRASAD[index-1];
     this.available_qty = this.Batch['avl_qty'];
     this.batch_no = this.Batch['batch_no'];
 
  }

   

  getFGMaterials(value) {
    this.service.observableFGTypes.subscribe(response => {
      // let data =response;
      if (value == "") {
        return;
      }
      let idx = -1;
      for (let i = 0; i < response.length; i++) {
        if (response[i]['material_subtype'] == value) {
          idx = i;
        }
      }
      if (idx >= 0) {
        let data = response[idx]
        this.fg_sub_materials = data['sub_materials'];

      } else {
        this.fg_sub_materials = []
      }
    })
  }
}
