import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit {
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
  isMrp=false;
  isHistory=false;

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
  //----------------------For Pagination---------------------------------//

  currentPage: number = 1;
  pageSize: number = 10; // Default page size

  calculateStartSrNo(): number {
    return (this.currentPage - 1) * 10 ;
  }
  
  onPageChange(page: number) {
    this.currentPage = page;
    console.log(this.currentPage);
  }
  
  onPageSizeChange(event: any) {
    this.pageSize = parseInt(event.target.value, 10); // Parse the selected value to an integer
  }
  viewf(){
    this.isView=false;
    //  this.getLogs();
    this.currentPage=1;
    this.pageSize =10;
    
  }
  // ---------------------------------------------------------------------//
 
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
    this.product_type=this.selectedResult['product_type'];
    this.product_nature=this.selectedResult['product_nature'];
    this.product_name=this.selectedResult['product_name'];
    this.uom=this.selectedResult['uom'];
    this.alt_uom=this.selectedResult['alt_uom'];
    this.grade=this.selectedResult['grade'];
    this.color_index=this.selectedResult['color_index'];
    this.ce_number=this.selectedResult['ce_number'];
    this.other_description=this.selectedResult['other_description'];
    this.product_apperance=this.selectedResult['product_apperance'];
    this.safety_instructions=this.selectedResult['safety_instructions'];
    this.storage_condition=this.selectedResult['storage_condition'];  
    this.cas_number=this.selectedResult['cas_number'];
    this.qc_lead_time=this.selectedResult['qc_lead_time'];
    this.pka_value=this.selectedResult['pka_value'];
    this.market_type=this.selectedResult['market_type'];
    this.manufactured_under=this.selectedResult['manufactured_under'];
    this.molecular_weight=this.selectedResult['molecular_weight'];
    this.molecular_formula=this.selectedResult['molecular_formula'];
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
      window.open(this.service.url + '../../upload/product/' + file);
    } else {
      alertify.error('File not available');
    }
  }
  openfsc(file) {
    if (file !== '') {
      window.open(this.service.url + '../upload/product/' + file);
    } else {
      alertify.error('File not available');
    }
  }
  opencopp(file) {
    if (file !== '') {
      window.open(this.service.url + '../upload/product/' + file);
    } else {
      alertify.error('File not available');
    }
  }
  opencertificate(file) {
    if (file !== '') {
      window.open(this.service.url + '../upload/product/' + file);
    } else {
      alertify.error('File not available');
    }
  }
  openphoto(file) {
    if (file !== '') {
      window.open(this.service.url + '../upload/product/' + file);
    } else {
      alertify.error('File not available');
    }
  }
  saveMrp(data) {
    this.service.post('master/mrp.php?type=saveMrp', JSON.stringify(data.value)).subscribe(response => {
      if (response['status'] == 'success') {
        data.reset();
        this.isMrp = false;
        alertify.success(' saved successfully');
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

}
