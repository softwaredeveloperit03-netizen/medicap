import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewReviewComponent implements OnInit {

  isView = false;
  samples;
  

  selectedResult = [];
  constructor(private service: DataAccessService,private router: Router ) {}

  ngOnInit() {
    this.getControlsamples();
  }

  getControlsamples() {
    this.service.get('qa/controlsample.php?type=getControlSamples').subscribe(response => {
      this.samples=response;
     
    });
  }
  getprint() {
    this.service.open('qa/controlsample.php?type=review_log');
  }
  


  view(index){
    this.selectedResult = this.samples[index];
    this.isView = true;
  }

  saveForm(data){
    if(!data.valid){
      alert('All fields are requried!');
      return;
    }
    this.service.post('qa/controlsample.php?type=saveControlSampleReview&id=' + this.selectedResult['id'], JSON.stringify(data.value)).subscribe(response => {
      if(response['status'] == 'success'){
        alert('Data Saved Successfully!')
        this.isView = false;
        this.getControlsamples();
      }else{
        alert('An Error occured, Please try again!');
      }
    });
  }

  close() {
    this.router.navigate(['/controlsample']);
  }
}
