import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css']
})
export class ApprovalComponent implements OnInit {
  phisicians;
  isView = false;
  results;
  selectedResult = [];
  data = [];
  doctor_name='';
    emp_id: string;
    isDIGI=false
    Status: any;
  constructor(private service: DataAccessService) { }

  ngOnInit():void {
    this.getPendingPhysicians();
    this.getApprovedPhisicians();
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

  getPendingPhysicians() {
    this.service.get('hr/physician.php?type=getPendingPhysicians').subscribe(response => {
      this.results = response;
      this.filterData();
    });
  }
  getApprovedPhisicians() {
    this.service.get('hr/physician.php?type=getPhysicians')
    .subscribe(response => {
      this.phisicians = response;
    });
  }
  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  update(status) {
    this.service.get('hr/physician.php?type=updatePhysician&status=' + status + '&id=' + this.selectedResult['id']).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('YES');
        this.isView = false;
        this.getPendingPhysicians();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }
  filterData() {
    this.data = [];
    for (let i = 0; i < this.results.length; i++) {
      let doctor = this.results[i];
      if (doctor['doctor_name'].toUpperCase().includes(this.doctor_name.toUpperCase())) {
        this.data[this.data.length] = doctor;
      }
    }
  }

  openDigiSign(status){
    this.emp_id = localStorage.getItem('emp_id');
    this.isDIGI = true;
    this.Status=status
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
        this.loginPassward ='';
        this.update(this.Status)

        
      
        
      }
      else
      {
        alertify.error('Digi-Sign Not Verified');

      }
    });
  }
  

}
