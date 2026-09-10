import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-increment-promotion',
  templateUrl: './increment-promotion.component.html',
  styleUrls: ['./increment-promotion.component.css']
})
export class IncrementPromotionComponent implements OnInit {
  generatedletter = [];
  employeelist = [];
  employee = [];

  isNew = false;
 
  isMetro = 'Non Metro';
  isPF = 'Yes';
  isESIC = 'No';

  gross = 0;
  deduction = 0;
  contribution = 0;
  inhand = 0;
  ctc = 0;

  basic = 0;
  HRA = 0;
  Conveyance = 0;
  medical = 0;
  specialAllowance = 0;
  educationalAllowance = 0;

  PF = 0;
  ESIC = 0;
  c_ESIC = 0;
  bonus = 0;
  p_tax = 0;
  canteen = 0;
  other = 0;

  letter_type;
  designationlist;
  emp_id;
  current_designation;
  current_gross;

  constructor(private service: DataAccessService) {}

  ngOnInit() {
    this.getgenerated();
    this.getemployee();
    this.getApprovedDepartments();
  }
  closeform(LetterForm){
    this.isNew = false;
    LetterForm.resetForm();
  }
  getApprovedDepartments() {
    this.service.get('hrDepartment.php?type=getApprovedDesignations').subscribe((response:any) => {
      this.designationlist = response;
    });
  }
  getemployee() {
    this.service.get('hrDepartment.php?type=generatedappointment').subscribe((response:any) => {
      this.employeelist = response;
    });
  }
  selectedemployee(index){
    this.emp_id = this.employeelist[index].emp_id;
    this.current_designation = this.employeelist[index].designation;
    this.current_gross = this.employeelist[index].current_gross;
  }
  getgenerated(){
    this.service.get('hrDepartment.php?type=generatedincremetpromotion').subscribe((response : any) => {
      this.generatedletter = response;
    });
  }
  submiting = false;
  generate(LetterForm){
    if(LetterForm.valid){
      this.submiting = true;
      const temp = LetterForm.value;
      temp['emp_id'] = this.emp_id;
      this.service.post('hrDepartment.php?type=newincrementpromotion', JSON.stringify(temp)).subscribe(response => {
        if(response['status'] == 'success'){
          this.isNew = false;
          this.submiting = false;
          alert('Record Generated Successfully !');
          this.getgenerated();
          this.current_designation = '';
          this.current_gross = '';
          LetterForm.resetForm();
        }
      });
    } else {
      this.submiting = false;
      alert('Fill All Feilds');
    }
  }

  downloadPdf(id) {
    window.open(this.service.url+'pdf/pdfhrDepartment.php?type=downloadincrementpromotion&id='+id+'&token=' + localStorage.getItem('token'));
  }

  emailOffer(id) {
    this.service.get('pdf/pdfhrDepartment.php?type=emailincrementpromotion&id='+id).subscribe(response => {
      if (response['status'] == "success") {
        alert('Email send successfully');
      } else {
        alert('An error occured');
      }
    });
  }

}
