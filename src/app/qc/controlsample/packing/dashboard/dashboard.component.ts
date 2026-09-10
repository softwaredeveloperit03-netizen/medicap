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
   from_date = '';
  to_date = '';
  selectedResult=[];
  isView=false;

  constructor(private service: DataAccessService, private datePipe: DatePipe,private router:Router) {
    this.from_date = this.datePipe.transform(Date.now(),'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(),'yyyy-MM-dd');
   }

  ngOnInit(): void {
    this.getPackingControlSamples();
    this.getUnits();
  }

  units;
  getUnits() {
    this.service.get('common.php?type=getUnits').subscribe(response => {
      this.units = response;
    });
  }

  material_type = 'Packing Material';

  getPackingControlSamples() {
    this.service.get('qc/testing/raw.php?type=getTestingReportforcs&material_type='+this.material_type).subscribe(response => {
      this.results = response;
    });
  }

  download() {
    this.service.open('qa/controlsample.php?type=downloadPackingControlSamples&material_type=' + this.material_type + '&from_date=' + this.from_date + '&to_date=' + this.to_date);
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
    let temp = data.value;
    temp['mfg_date'] = this.selectedResult['mfg_date'];
    temp['batch_no'] = this.selectedResult['batch_no'];
    temp['exp_date'] = this.selectedResult['exp_date'];
    temp['sampling_date'] = this.selectedResult['sampling_start_time'];
    temp['control_sample'] = this.selectedResult['control_sample'];
    temp['ar_no'] = this.selectedResult['ar_no'];
    temp['material_code'] = this.selectedResult['material_code'];
    temp['analysis_date'] = this.selectedResult['alalysis_start_date'];
    temp['release_date'] =  '';
    temp['sample_by'] =  this.selectedResult['sample_by'];
  
    console.log(temp);

    this.service.post('qa/controlsample.php?type=savePackingControlSample', JSON.stringify(temp)).subscribe(response => {
      const result = JSON.parse(JSON.stringify(response));
      if (result.status === 'success') {
        alertify.success( 'Saved Successfully');
        this.isView=false;
        data.reset();
       } else {
       alertify.error(this.service.t('common.errorOccurred'));
      }
    });

    
  }





}
