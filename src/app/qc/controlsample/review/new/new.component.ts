import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewReviewComponent implements OnInit {

  isView = false;
  samples;
  material_type = '';
  

  selectedResult = [];
  constructor(private service: DataAccessService,private router: Router ) {}

  ngOnInit() {
   // this.getControlsamples();
  }

  getControlsamples(value) {
    this.service.get('qa/controlsample.php?type=getControlSamples&material_type='+value).subscribe(response => {
      this.samples=response;
     
    });
  }
  


  view(index){
    this.selectedResult = this.samples[index];
    this.isView = true;
  }

  saveForm(data){
    if(!data.valid){
      alertify.warning('All fields are requried!');
      return;
    }
    this.service.post('qa/controlsample.php?type=saveControlSampleReview&id=' + this.selectedResult['id'], JSON.stringify(data.value)).subscribe(response => {
      if(response['status'] == 'success'){
        alertify.success('Data Saved Successfully!')
        this.isView = false;
        this.getControlsamples(this.material_type);
      }else{
        alertify.error('An Error occured, Please try again!');
      }
    });
  }

  close() {
    this.router.navigate(['/controlsample']);
  }
}
