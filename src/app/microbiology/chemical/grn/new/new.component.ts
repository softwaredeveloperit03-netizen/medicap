import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {

  isView = false;

  results;
  selectedReport = [];

  remark = '';
    emp_id: string;
    isDIGI: boolean=false
    isbutton: boolean=true
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingGRN();
    this.getgensubtype();
  }

  material_subtype = 'Cultures';
  
  getPendingGRN() {
    this.service.get('qc/chemical.php?type=getPendingGRN&material_subtype='+this.material_subtype).subscribe(response => {
      this.results = response;
    });
  }

  subtypes;
  getgensubtype() {
    this.service.get('master/materialtype.php?type=get_gen_material_subtype&material_type=Microbiology Materials').subscribe((response: any) => {
      this.subtypes = response;
    });
  }

  viewResult(index) {
    this.selectedReport = this.results[index];
    this.isView = true;
  }


  batch_details =[];


  addBatchDetails(data){
    if (!data.valid) {
      alertify.error('All fields are required!');
      return;
    }
    let temp = data.value;
    temp['material_code'] = this.selectedReport['material_code'];
    temp['material_subtype'] = this.selectedReport['material_subtype'];
    temp['material_type'] = this.selectedReport['material_type'];
    temp['grn_no'] = this.selectedReport['grn_no'];
    temp['pack_size'] = this.selectedReport['pack_size'];
     this.batch_details.push(temp);
    data.reset();
  }

  delBatchDetails(index){
    this.batch_details.splice(index,1);
  }


  
  number(value){
    if (isNaN(value)){
      alertify.error('Number Only');
      return false;
    }
  }



  exp_date ='';
  batch_no ='';
  mfg_date ='';

  openDigiSign(value){
    this.emp_id = localStorage.getItem('emp_id');
    this.isDIGI = true;
  }

  loginPassward ='';
  digiSign(data){

    if (!data.valid) {
      alert('Passward OR Login PIN Required!!!!');
      return;
    }
 
    this.service.get('login.php?type=checkDigiSIgn&mpin=' + this.loginPassward +'&emp_id=' + this.emp_id).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Digi-Sign Verified successfully');
        this.isDIGI = false;
        this.isbutton = false;
        this.loginPassward ='';
        this.prepareGRN()
      } else {
        alertify.error('Digi-Sign Not Verified');
      }
    });
  }

  prepareGRN() {
    let temp = {};
  
    temp['id']=this.selectedReport['id'];
    temp['batches']=this.batch_details;
     this.service.post('qc/chemical.php?type=update_batch_details&material_subtype='+this.material_subtype, JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('GRN Prepared successfully');
        this.isbutton=true
        this.isView = false;
        this.getPendingGRN();
        this.exp_date ='';
        this.batch_no ='';
        this.mfg_date ='';
        this.remark='';
        this.batch_details =[];
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }



   
   
  preparearray(value) {
    if (isNaN(value)){
      alertify.error('Number Only');
      return false;
    }
    this.batch_details =[];
    const length = parseInt(value);
    for(let i =0; i < length; i++){
      let temp ={
        exp_date: '',
        mfg_date: '',
        received_qty: '1',
        material_code: this.selectedReport['material_code'],
        material_subtype: this.selectedReport['material_subtype'],
        material_type: this.selectedReport['material_type'],
        pack_size: this.selectedReport['pack_size'],
        grn_no: this.selectedReport['grn_no'],
        batch_no: ''
      };
  
      this.batch_details.push(temp);
    }
  }


















}
