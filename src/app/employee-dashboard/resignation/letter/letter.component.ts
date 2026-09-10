import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-letter',
  templateUrl: './letter.component.html',
  styleUrls: ['./letter.component.css']
})
export class LetterComponent implements OnInit {

  isNew = false;
  letterlist =[];
  employeelist = [];
  resignations;
  isShow = false;
  selectedResignation;
  id;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getLetters();
    this.getemployee();
  }

  getLetters() {
    this.service.get('hr/resignation.php?type=getexperienceletter').subscribe((response:any) => {
      this.letterlist = response;
    });
  }
  getemployee() {
    this.service.get('hr/resignation.php?type=getPendingExperience').subscribe((response:any) => {
      this.employeelist = response;
    });
  }
  ;
  selectedemp;
  entry_date;
  viewemployeedetail(index){
    this.selectedemp = this.employeelist[index];
    this.entry_date = this.selectedemp['entry_date'];
    this.id = this.selectedemp['id'];
    console.log(this.entry_date);
  }
  reginationForm;
  generateletter(form){
    if(form.valid){
      this.service.post('hr/resignation.php?type=generateexpletter', JSON.stringify(form.value)).subscribe((response : any) => {
        if(response['status'] == 'success'){
          alert('Record Inserted Successfully !');
          this.isNew = false;
          this.selectedemp = [];
          this.entry_date = '';
          this.id = '';
          this.getLetters();
        } else {
          alert('Something went wrong, Please try again');
        }
      })
    } else {
      alert('All Feilds are required');
    }
  }
  downloadPdf(id) {
    window.open(this.service.url+'hrDepartment.php?type=printexpletter&id='+id +'&token='+localStorage.getItem('token')+'','_blank');
  }

  email(id) {
    this.service.get('pdfhrDepartment.php?type=emailexperienceletter?id='+id +'&token='+localStorage.getItem('token')).subscribe((Response:any) =>{
      if(Response['status'] == 'success'){
        alert('Email Send Sussfully')
      }
    });
  }

}
