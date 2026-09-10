import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import { DatePipe } from '@angular/common';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';




@Component({
  selector: 'app-dossierreq',
  templateUrl: './dossierreq.component.html',
  styleUrls: ['./dossierreq.component.css'],
  providers:[DatePipe]

})
export class DossierreqComponent implements OnInit {

  formopen = false;
  clients;
  isView = false;
  isNew = false;
  entries;
  selectedEntry;
  packing_config = '';
  configurations = [];
  // tslint:disable-next-line: variable-name
  company = '';
  Dossiers = [];
  file: File;

  DossierForm : FormGroup;
  // tslint:disable-next-line: variable-name
  enquiry_for = '';
  list;
  isApprover;
  special_remark ='';
  approval_remark ='';
  acceptance_remark ='';

  constructor(private service: DataAccessService, private router: Router, private fb: FormBuilder) {

    this.DossierForm = this.fb.group({
      api_name: ['', [Validators.required]],
      strength: ['', [Validators.required]],
      api_grade: ['', [Validators.required]]
    });
   }
  ngOnInit() {

    this.getDossierList();
    this.getProduct();

    if (localStorage.getItem('approver') === 'true') {
      this.isApprover = true;
     } else {
      this.isApprover = false;
     }
  }

  pType = 'Existing Product';


  getProduct() {
    this.service.get('common.php?type=getAllProducts').subscribe(response => {
      this.products = response;
    })
  }
  products;

  selectedProduct=[];
  selectProduct(ind){
    let index = ind -1 ;

    this.selectedProduct =  this.products[index];

    let lbl = this.selectedProduct['label_claim'][0];

    let label_claim = "Each"+" "+lbl.dose_unit_type+" "+"Contains"+" "+lbl.material_name+" "+lbl.grade

    let rem ="";
    this.label_claim = label_claim;
    if(lbl.equivalent_to!==''){

      rem = lbl.equivalent_to +" "+ lbl.strength +" "+ lbl.unit +" "+" -------- ( "+" "+lbl.apperance +" "+" )";

      this.label_claim = label_claim +" "+rem;
    }

    
  }





//   <span *ngIf="label.equivalent_to!==''">equivalent to <br>
//   {{ label.equivalent_to }} {{ label.strength }} {{ label.unit
//   }} -------- ({{label.apperance}})
// </span>





  label_claim ='';


  addDossiers() {
    this.Dossiers.push({
      api_name: this.DossierForm.value.api_name,
      strength: this.DossierForm.value.strength,
      api_grade: this.DossierForm.value.api_grade
    });
    this.DossierForm.reset();
  }

  deleteDossiers(index) {
    this.Dossiers.splice(index, 1);
  }

  viewDossier(index) {
    this.selectedEntry = this.list[index];
    this.isView = true;
  }

  getDossierList() {
    this.service.get('marketing.php?type=getDossierList').subscribe(response => {
      this.list = response;
    });
  }

  onFileChange($event) {
    this.file = $event.target.files[0];
   }

   updateDossierRequisition(action) {
    this.service.get('marketing.php?type=updateDossierRequisition&id=' + this.selectedEntry.id +
    '&action=' + action + '&approval_remark=' + this.approval_remark  + '&acceptance_remark=' + this.acceptance_remark).subscribe(response => {
      alert('Updated Successfully');
      this.getDossierList();
      this.isView = false;
      this.approval_remark = '';
      this.acceptance_remark = '';
    });
  }

   saveForm(data) {
      const formData = new FormData();

      formData.append('brand_name', data.value.brand_name);
      formData.append('generic_name', data.value.generic_name);

      formData.append('dossiers', JSON.stringify(this.Dossiers));

      formData.append('label_claim', data.value.label_claim);
      formData.append('finished_product_life', data.value.finished_product_life);
      formData.append('packing_style', data.value.packing_style);
      formData.append('country', data.value.country);

      formData.append('mfg_by', data.value.mfg_by);
      formData.append('marketed_by', data.value.marketed_by);
      formData.append('mfg_address', data.value.mfg_address);

      formData.append('marketing_address', data.value.marketing_address);
      formData.append('applicant_name', data.value.applicant_name);
      formData.append('market_product', data.value.market_product);
      formData.append('dossier_lang', data.value.dossier_lang);

      formData.append('registered_product', data.value.registered_product);
      formData.append('client_name', data.value.client_name);
      formData.append('document_checklist', data.value.document_checklist);
      formData.append('requisition_date', data.value.requisition_date);

      formData.append('expected_date', data.value.expected_date);
      formData.append('requisition_prepared', data.value.requisition_prepared);
      formData.append('special_remark', data.value.special_remark);

      formData.append('approval_remark', data.value.approval_remark);
      formData.append('acceptance_remark', data.value.acceptance_remark);
      // formData.append('file', this.file, this.file.name);

      this.service.post('marketing/dossier.php?type=saveDossier', formData).subscribe(response => {
        const result = JSON.parse(JSON.stringify(response));
        if (result.status === 'success') {
          this.getDossierList();
          data.resetForm();
          this.isNew = false;
          this.Dossiers = [];
          alert('Saved Successfully');
          this.router.navigate(['/marketing/dossier']);
        } else {
          alert('An error has occurred, please try again');
        }
        },
      (error: Response) => {
        if (error.status === 400) {
          alert('An error has occurred.');
        } else {
          alert('An error has occurred, http status:' + error.status);
        }
      });
    }

  addclientbtn() {
    this.formopen = true;
  }
  closeclientbtn() {
    this.formopen = false;
  }

  close() {
    this.router.navigate(['/dashboard']);
  }




}
