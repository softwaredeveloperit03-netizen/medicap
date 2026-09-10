import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
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
    router: any;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getLetters();
    this.getemployee();
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
    //this.isView=false;
    //  this.getLogs();
    this.currentPage=1;
    this.pageSize =10;
    
  }
  // ---------------------------------------------------------------------//

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
  selectedemp=[];
  entry_date;
  releaving_date;
  resignation_date;
  viewemployeedetail(index){
    this.selectedemp = this.employeelist[index-1];
    this.resignation_date = this.selectedemp['resignation_date'];
    this.releaving_date = this.selectedemp['releaving_date'];
    this.id = this.selectedemp['id'];
    console.log(this.selectedemp);
  }
  reginationForm;
  // save(form){
  //   if(form.valid){
  //     this.service.post('hr/resignation.php?type=generateexpletter', JSON.stringify(form.value)).subscribe((response : any) => {
  //       if(response['status'] == 'success'){
  //         alert('Record Inserted Successfully !');
  //         this.isNew = false;
  //         this.selectedemp = [];
  //         this.entry_date = '';
  //         this.id = '';
  //         this.getLetters();
  //       } else {
  //         alert('Something went wrong, Please try again');
  //       }
  //     })
  //   } else {
  //     alert('All Feilds are required');
  //   }
  // }
  generateletter(Form){
    if(!Form.valid){
      alertify.error('All fields are required');
      return;
    }
    this.service.post('hr/resignation.php?type=generateexpletter',JSON.stringify(Form.value)).subscribe(response=>{
      if(response['status']==='success'){
         alertify.success('data save Successfuly');
        Form.resetForm();
      }else{
        alertify.error('Error Occured');
      }
    });
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
