import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
  providers:[DatePipe]
})
export class DashboardComponent implements OnInit {

  results;
  material_type = '';
  from_date = '';
  to_date = '';
  selectedResult=[];
  isView=false;
  units;

  constructor(private service:DataAccessService, private datePipe: DatePipe,private router:Router) {
    this.from_date = this.datePipe.transform(Date.now(),'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(),'yyyy-MM-dd');
  }

  ngOnInit(): void {
    // this.getRawControlSamples();
    this.getTestings();
    this.getUnits();

  }

  getUnits() {
    this.service.get('common.php?type=getUnits').subscribe(response => {
      this.units = response;
    });
  }
  // getRawControlSamples(){
  //   this.service.get('qa/controlsample.php?type=getRawControlSamples&material_type=' + this.material_type + '&from_date=' + this.from_date + '&to_date=' + this.to_date).subscribe(response => {
  //     this.results = response;
  //   });
  // }

  getTestings() {
    this.service.get('qc/testing/raw.php?type=getTestingReport&from_date='+this.from_date+'&to_date='+this.to_date).subscribe(response => {
      this.results = response;
    });
  }


  download() {
    this.service.open('qa/controlsample.php?type=downloadRawControlSamples&material_type=' + this.material_type + '&from_date=' + this.from_date + '&to_date=' + this.to_date);
  }
  view(index){
    this.selectedResult=this.results[index];
    this.isView=true;
  }
  saveForm(data) {
    if (!data.valid) {
      alertify.warning('All fields are required!');
      return;
    }
    this.service.post('qa/controlsample.php?type=saveRawControlSample', JSON.stringify(data.value)).subscribe(response => {
      const result = JSON.parse(JSON.stringify(response));
      if (result.status === 'success') {
        alertify.success( 'Saved Successfully');
        this.router.navigate(['/qa/controlsample/raw']);
      } else {
       alertify.error(this.service.t('common.errorOccurred'));
      }
    });
  }

}
