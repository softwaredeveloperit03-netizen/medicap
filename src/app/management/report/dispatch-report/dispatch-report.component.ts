import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-dispatch-report',
  templateUrl: './dispatch-report.component.html',
  styleUrls: ['./dispatch-report.component.css']
})
export class DispatchReportComponent implements OnInit {
//   isView = false;
//   isNew = false;
//   clients = [];
//   salesorders = [];
//   dispatchedProducts = [];

//   addedProductList = [];

//   selectedClient;
//   selectedProduct;

//   clientForm: FormGroup;
//   reactiveForm: FormGroup;
//   constructor(private service: DataAccessService, private fb: FormBuilder) { }

//   ngOnInit() {
//     this.clientForm = this.fb.group({
//       client: ['', [Validators.required]],
//       person: { value: '', disabled: true },
//       phone: { value: '', disabled: true },
//       email: { value: '', disabled: true },
//       gst_type: { value: '', disabled: true }
//     });

//     this.reactiveForm = this.fb.group({
//       new_material: ['', [Validators.required]],
//       hsn: { value: '', disabled: true },
//       avl_qty: { value: '', disabled: true },
//       description: { value: '', disabled: true },
//       total_qty: ['', [Validators.required]],
//       rate: ['', [Validators.required]],
//       value: ['', [Validators.required]],
//       disc_per: ['', [Validators.required]],
//       discount: ['', [Validators.required]],
//       taxable: ['', [Validators.required]]
//     });
//     this.getClients();
//     this.getDispatchProducts();
//     this.getSalesOrders();
//   }

//   getClients() {
//     this.service.get('dispatch.php?type=getClients').subscribe(response=> {
//       this.clients = JSON.parse(JSON.stringify(response));
//     });
//   }

//   getDispatchProducts() {
//     this.service.get('dispatch.php?type=getDispatchProducts').subscribe(response => {
//       this.dispatchedProducts = JSON.parse(JSON.stringify(response));
//     });
//   }
  
//   getSalesOrders() {
//     this.service.get('dispatch.php?type=getSalesOrders').subscribe((response: any) => {
//       this.salesorders = JSON.parse(JSON.stringify(response));
//     });
//   }

//   onClientChange(index) {
//     this.selectedClient = this.clients[index];
//     this.clientForm.patchValue({
//       person: this.selectedClient.person,
//       phone: this.selectedClient.phone,
//       email: this.selectedClient.email,
//       gst_type: this.selectedClient.gst_type
//     });
//     this.addedProductList = [];
//   }

//   onProductChange(index) {
//     this.selectedProduct = this.dispatchedProducts[index];
//     this.reactiveForm.patchValue({
//       hsn: this.selectedProduct.hsn,
//       description: this.selectedProduct.label_claim,
//       avl_qty: this.selectedProduct.qty + ' ' + this.selectedProduct.unit
//     });
//   }

//   addProduct() {
//     const data = {
//       product_code: this.selectedProduct.product_code,
//       new_material: this.selectedProduct.product_name,
//       hsn: this.selectedProduct.hsn,
//       description: this.selectedProduct.label_claim,
//       total_qty: this.reactiveForm.value.total_qty,
//       rate: this.reactiveForm.value.rate,
//       value: this.reactiveForm.value.value,
//       disc_per: this.reactiveForm.value.disc_per,
//       discount: this.reactiveForm.value.discount,
//       taxable: this.reactiveForm.value.taxable,
//       sgst_rate: 0,
//       sgst_amount: 0,
//       cgst_rate: 0,
//       cgst_amount: 0,
//       igst_rate: 0,
//       igst_amount: 0,
//       total_value: 0
//     };
//     this.addedProductList.push(data);
//     this.reactiveForm.reset();
//   }

//   onProductDelete(index) {
//     this.addedProductList.splice(index, 1);
//   }
  
//   onSubmit() {
//     const taxable = '0';
//     const tax = '0';
//     const total = '0';

//     const formData = new FormData();
//     formData.append('client_code', this.selectedClient.client_code);
//     formData.append('taxable', taxable);
//     formData.append('tax', tax);
//     formData.append('total', total);
//     formData.append('products', JSON.stringify(this.addedProductList));

//     this.service.post('dispatch.php?type=saveSalesOrder', formData).subscribe(response => {
//       const result = JSON.parse(JSON.stringify(response));
//       if (result.status == "success") {
//         this.isNew = false;
//         this.reactiveForm.reset();
//         this.getSalesOrders();
//       } else {
//         alertify.success(result.status);
//       }
//     });
//   }

//   onOrderView(item) {
//     this.isView = false;
//     this.selectedClient = item;
//   }

//   onPDFView(item) {
//     const url = this.service.url + 'pdf1/dispatch.php?type=generateDispatchPDF&order_id=' + item.order_id + '&client_code=' + item.client_code + '&token=' + localStorage.getItem('token');
//     window.open(url, "_blank");
//   }

// }



results;
results1;
type='';
product_type = '';
product_nature = '';
manufactured_under = '';
manufactured_for = '';
grade='';
grades;
product_apperance='';
clients;
selectedResult=[];
isEdit=false;
id='';
isDelete=false;
plants;
isView=false;

product_name = '';
product_code = '';
selectedPlants;
products= [];
storage_condition='';
market_type='';
pka_value='';
qc_lead_time='';
cas_number='';
safety_instructions='';
other_description='';
ce_number='';
color_index='';
alt_uom='';
uom='';
molecular_formula='';
molecular_weight='';
constructor(private service: DataAccessService) { }

ngOnInit(): void {
  this.service.observableGrade.subscribe(response => {
    this.grades = response;
    this.service.get('common.php?type=getClients').subscribe(response => {
      this.clients = response;
    });
  });
  this.getProducts();
}

getProducts() {
  this.service.get('master/product.php?type=getProductsLog').subscribe((response: any) => {
    this.results = response;
    this.filterProduct();
  });
}



filterProduct() {
  this.products = [];
  for (let i = 0; i < this.results.length; i++) {
    let material = this.results[i];
    if (material['product_type'].toUpperCase().includes(this.product_type.toUpperCase())&& material['grade'].toUpperCase().includes(this.grade.toUpperCase()) && material['product_name'].toUpperCase().includes(this.product_name.toUpperCase()) && material['product_code'].toUpperCase().includes(this.product_code.toUpperCase())) {
      this.products[this.products.length] = material;
    }
  }
}

getClients(value) {
  if (value == 'Client') {
    this.service.get('common.php?type=getClients').subscribe(response => {
      this.clients = response;
    });
  } else {
    this.manufactured_for = '';
  }
}

download() {
  this.service.open('master/product.php?type=downloadProductsLog&grade='+this.grade )
}

edit(index){
  this.selectedResult=this.products[index];
  this.product_name=this.selectedResult['product_name'];
  this.product_type=this.selectedResult['product_type'];
  this.grade=this.selectedResult['grdae'];
  this.id=this.selectedResult['id'];
  this.product_apperance=this.selectedResult['product_apperance'];
  this.storage_condition=this.selectedResult['storage_condition'];
  this.manufactured_under=this.selectedResult['manufactured_under'];
  this.manufactured_for=this.selectedResult['manufactured_for'];
  this.type=this.selectedResult['type'];
  this.isEdit=true;
}
del(index){
  this.selectedResult=this.products[index];
  this.isDelete=true;
}


view(index){
  this.selectedResult=this.products[index];
  this.product_type = this.selectedResult['product_type'] || 'NA';
  this.product_nature = this.selectedResult['product_nature'] || 'NA';
  this.product_name = this.selectedResult['product_name'] || 'NA';
  this.uom = this.selectedResult['uom'] || 'NA';
  this.alt_uom = this.selectedResult['alt_uom'] || 'NA';
  this.grade = this.selectedResult['grade'] || 'NA';
  this.color_index = this.selectedResult['color_index'] || 'NA';
  this.ce_number = this.selectedResult['ce_number'] || 'NA';
  this.other_description = this.selectedResult['other_description'] || 'NA';
  this.product_apperance = this.selectedResult['product_apperance'] || 'NA';
  this.safety_instructions = this.selectedResult['safety_instructions'] || 'NA';
  this.storage_condition = this.selectedResult['storage_condition'] || 'NA';  
  this.cas_number = this.selectedResult['cas_number'] || 'NA';
  this.qc_lead_time = this.selectedResult['qc_lead_time'] || 'NA';
  this.pka_value = this.selectedResult['pka_value'] || 'NA';
  this.market_type = this.selectedResult['market_type'] || 'NA';
  this.manufactured_under = this.selectedResult['manufactured_under'] || 'NA';
  this.molecular_weight = this.selectedResult['molecular_weight'] || 'NA';
  this.molecular_formula = this.selectedResult['molecular_formula'] || 'NA';
  this.isView=true;
}

editProduct(data){
  let temp=data.value;
  temp['id']=this.id;
   this.service.post('master/product.php?type=editProduct',JSON.stringify(temp)).subscribe(response=>{
      if(response['status']=='success'){
        alertify.success('Product Updates Successuly');
        this.product_name='';
        this.product_code='';
        this.grade='';
        this.isEdit=false;
        this.getProducts();
      }else{
        alertify.error(response['status']);
      }
    });
  }

delete(){
this.service.get('master/product.php?type=deleteProduct&id='+this.selectedResult['id']).subscribe(response=>{
  if(response['status']){
    alertify.success('Product Deleted Successuly');
    this.isDelete=false;
    this.getProducts();
  }else{
    alertify.error('some error occured');
  }
});
}

AllRecord(){
    this.products =this.results;
    this.product_type='';
    this.grade='';
    this.product_name = '';
    this.product_code = '';
}

openlic(file) {
  if (file !== '') {
    window.open(this.service.url + 'upload/product/' + file);
  } else {
    alertify.error('File not available');
  }
}
openfsc(file) {
  if (file !== '') {
    window.open(this.service.url + 'upload/product/' + file);
  } else {
    alertify.error('File not available');
  }
}
opencopp(file) {
  if (file !== '') {
    window.open(this.service.url + 'upload/product/' + file);
  } else {
    alertify.error('File not available');
  }
}
opencertificate(file) {
  if (file !== '') {
    window.open(this.service.url + 'upload/product/' + file);
  } else {
    alertify.error('File not available');
  }
}
openphoto(file) {
  if (file !== '') {
    window.open(this.service.url + 'upload/product/' + file);
  } else {
    alertify.error('File not available');
  }
}

}
