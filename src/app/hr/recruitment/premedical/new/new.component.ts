import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {
  candidate;
  phisicians;
  constructor(private service:DataAccessService) { }

  ngOnInit() {
    this.getCandidates();
    this.getApprovedPhisicians();
  }

  getCandidates() {
    this.service.get('hr/medical.php?type=getCandidates').subscribe(response=>{
      this.candidate=response;
    });
  }
  getApprovedPhisicians(){
    this.service.get('hr/medical.php?type=getPhysicians')
    .subscribe(response => { 
      this.phisicians = response;
    });
  }

  savePremedical(data) {
    if(!data.valid){
      alert('All Fields are required');
      return;
    }
    this.service.post('hr/medical.php?type=savePremedical',JSON.stringify(data.value)).subscribe(response=>{
      if(response['status']=='success'){
        alert('data saved Successfuly');
        data.resetForm();
        this.getCandidates();
      }else{
        alert('Some error Occured');
      }
    });
  }

}
