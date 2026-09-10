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

   units;
   revise = false;


   lic_data =[
    {peramt:25,term:'Upon Agreement Sign Off',amt:0,curr:''},
    {peramt:25,term:'Upon Dossier Submission in first Country Of The Territory',amt:0,curr:''},
    {peramt:25,term:'Upon answering of the queries in the First Country Of the Territory',amt:0,curr:''},
    {peramt:25,term:'Upon Receipt of First MA in First Country Of The Territory',amt:0,curr:''}
   ];

 
 
 
  constructor(public service: DataAccessService,private router:Router) { }

  ngOnInit() {
    this.get_rights();
    this.getUnits();
    this.getAddterm();
    this.getGeneralAddterm();
    this.getDossierAddterm();
    this.getRegulatoryAddterm();
    this.getPackingAddterm();
   }

   



     product_type = 'Finished Goods';

  types;
  getMaterialType(){
    this.service.get('master/materialtype.php?type=getMatTypeByMatType&material_type='+this.product_type).subscribe(response => {
      this.types= response;
     });
  }



  products: any;
  category = '';
  moq = 'One Full Batch';
  dosage_type = '';
  dosage_form = '';
 
getProductByDosageTypeForm() {
   const params = `master/product.php?type=getProductByDosageTypeForm`
    + `&category=${encodeURIComponent(this.category)}`
    + `&dosage_form=${encodeURIComponent(this.dosage_form)}`
    + `&dosage_type=${encodeURIComponent(this.dosage_type)}`
    + `&product_type=${encodeURIComponent(this.product_type)}`;

  this.service.get(params).subscribe((response) => {
    this.products = response;
  });
}


 
   currency ='';
   licAmount =0;


   calculateLicFee(i){
    let percentage = this.lic_data[i].peramt;
    this.lic_data[i].amt = Number(((percentage / 100) * this.licAmount).toFixed(2));

   }

   calculateLicAllFee(){
    for(let i = 0; i<this.lic_data.length;i++){
      let percentage = this.lic_data[i].peramt;
      this.lic_data[i].amt = Number(((percentage / 100) * this.licAmount).toFixed(2));
      this.lic_data[i].curr = this.currency;
    }
   }



 

  clientdata;

  isuser = 'No';
  ischecker = 'No';
  dept_head = 'No';
  rights;

  get_rights() {
   this.service.get('hr/employee.php?type=getrights&emp_id=' + localStorage.getItem('emp_id') +'&dep_name=' +localStorage.getItem('department') ).subscribe((response) => {
       this.rights = response;
       this.isuser = this.rights[0].isuser;
       this.ischecker = this.rights[0].ischecker;
       this.dept_head = this.rights[0].dept_head;
        this.getTempClient(this.dept_head);
    });
  }
  

 
  getTempClient(clientFor) {
    this.service.get('marketing/client.php?type=getClientForMarketing&clientFor='+clientFor).subscribe((response: any) => {
        this.clientdata = response;
    });
  }

 
  
 
  selectP=[];

 
  getProductDetails(index){
    index=index-1;
    if(index!=-1){
      this.selectP = this.products[index];
    }
  }

  getUnits() {
    this.service.get('common.php?type=getUnits').subscribe(response => {
      this.units = response;
    });
  }


   
  productList=[];

   addPeoduct(data){

    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp=data.value;
 
    temp['product_code']=this.selectP['product_code'];
    temp['generic_name']= this.selectP['generic_name'];

    this.productList[this.productList.length]=data.value;
    data.resetForm();
  }

  delProduct(index){
    this.productList.splice(index,1);
  }


 


 
 
 

 

  saveData(data){

    if (!data.valid) {
      alert('All fields are required');
      return;
    }

    let terms1 = [];
    let packingTerm = [];
    let regulatoryTerm = [];
    let dossierTerm = [];
    let generalTerm = [];
 
 
    for (let i = 0; i < this.packingTerm.length; i++) {
      let term = this.packingTerm[i];
      if (term['selected']) {
        packingTerm[packingTerm.length] = term;
      }
    }

    for (let i = 0; i < this.regulatoryTerm.length; i++) {
      let term = this.regulatoryTerm[i];
      if (term['selected']) {
        regulatoryTerm[regulatoryTerm.length] = term;
      }
    }

    for (let i = 0; i < this.dossierTerm.length; i++) {
      let term = this.dossierTerm[i];
      if (term['selected']) {
        dossierTerm[dossierTerm.length] = term;
      }
    }

    for (let i = 0; i < this.generalTerm.length; i++) {
      let term = this.generalTerm[i];
      if (term['selected']) {
        generalTerm[generalTerm.length] = term;
      }
    }

    for (let i = 0; i < this.terms.length; i++) {
      let term = this.terms[i];
      if (term['selected']) {
        terms1[terms1.length] = term;
      }
    }

    
    let temp=data.value;
    temp['licAmount'] = this.licAmount;
    temp['currency'] = this.currency;
    temp['lic_data'] = this.lic_data;
    temp['generalTerm'] = generalTerm;
    temp['dossierTerm'] = dossierTerm;
    temp['packingTerm'] = packingTerm;
    temp['regulatoryTerm'] = regulatoryTerm;

    temp['productList']=this.productList;
     temp['terms'] = terms1;

    this.service.post('marketing/quotation.php?type=saveQuotation',JSON.stringify(temp)).subscribe(response=>{
      if(response['status']=='success'){
        alert('Quotation Saved succesfuly');
       this.router.navigate(['/marketing/quotation']);
      }else{
        alert('Some Error Occured'); 
      }
    });
  }





  terms;
  isTerm = false;
  isEdit = false;

 isPacking = false;
 isRegulatory = false;
 isDossier = false;
 isGeneral = false;

 isPacking1 = false;
 isRegulatory1 = false;
 isDossier1 = false;
 isGeneral1 = false;
 
  

  saveQttermsForTerm(data: any,status) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    this.service
      .post('master/terms.php?type=saveQttermsForTerm&status='+status, JSON.stringify(data.value))
      .subscribe((response) => {
        if (response['status'] == 'success') {
           

           if(status == 'Packing'){
            this.isPacking = false;
            this.getPackingAddterm();
           }
           else if(status == 'Regulatory'){
            this.isRegulatory = false;
            this.getRegulatoryAddterm();
           }
           else if(status == 'Dossier'){
            this.isDossier = false;
            this.getDossierAddterm();
           }
           else if(status == 'General'){
            this.isGeneral = false;
            this.getGeneralAddterm();
           }
 
          alert('Record Inserted Successfully');
          data.resetForm();
        } else {
          alert('Please try Again');
        }
      });
  }




  saveTerm(data: any) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    this.service
      .post('master/terms.php?type=saveQuatationTerms', JSON.stringify(data.value))
      .subscribe((response) => {
        if (response['status'] == 'success') {
           this.getAddterm();
          this.isTerm = false;
          alert('Record Inserted Successfully');
          data.resetForm();
        } else {
          alert('Please try Again');
        }
      });
  }




  delTerm(id) {
    this.service.get('master/terms.php?type=deleteTerm&id=' + id).subscribe((response) => {
        
        if (response['status'] == 'success') {
          this.isEdit = false;
          alertify.success('Term Deleted Successfully');
          this.getAddterm();
        } else {
          alertify.error('Failed: An error occured, please try again!');
        }
      });
  }
  delQttermss(id,status) {
    this.service.get('master/terms.php?type=delQttermss&id=' + id).subscribe((response) => {
        
        if (response['status'] == 'success') {
           
          alertify.success('Term Deleted Successfully');
          if(status == 'Packing'){
            this.isPacking1 = false;
            this.getPackingAddterm();
           }
           else if(status == 'Regulatory'){
            this.isRegulatory1 = false;
            this.getRegulatoryAddterm();
           }
           else if(status == 'Dossier'){
            this.isDossier1 = false;
            this.getDossierAddterm();
           }
           else if(status == 'General'){
            this.isGeneral1 = false;
            this.getGeneralAddterm();
           }
        } else {
          alertify.error('Failed: An error occured, please try again!');
        }
      });
  }




  Addterms;



  getAddterm() {
    this.service.get('master/terms.php?type=getQuatationTerms').subscribe((response) => {
        this.terms = response;
    });
  }

  packingTerm;
  regulatoryTerm;
  dossierTerm;
  generalTerm;

  getPackingAddterm() {
    this.service.get('master/terms.php?type=getqtaddterms&status=Packing').subscribe((response) => {
        this.packingTerm = response;
    });
  }
  getRegulatoryAddterm() {
    this.service.get('master/terms.php?type=getqtaddterms&status=Regulatory').subscribe((response) => {
        this.regulatoryTerm = response;
    });
  }
  getDossierAddterm() {
    this.service.get('master/terms.php?type=getqtaddterms&status=Dossier').subscribe((response) => {
        this.dossierTerm = response;
    });
  }
  getGeneralAddterm() {
    this.service.get('master/terms.php?type=getqtaddterms&status=General').subscribe((response) => {
        this.generalTerm = response;
    });
  }


 



}

