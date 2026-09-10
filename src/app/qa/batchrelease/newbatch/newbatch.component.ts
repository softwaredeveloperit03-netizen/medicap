import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-new-batch',
  templateUrl: './newbatch.component.html'
})
export class NewBatchComponent implements OnInit {

  isView = false;
  results;

  selectedResult = [];
  isProducts = false;
  dosages;
  selectedBatch = [];
  constructor(private service: DataAccessService, private router: Router) {
  }

  ngOnInit() {
    this.getPendingBatchRelease();
  }

  getPendingBatchRelease() {
    this.service.get('batch-release.php?type=getPendingBatchRelease&dosage_form='+this.selectedResult['dosage_form']).subscribe(response => {
      this.results = response;
    });
  }
  getdosagechklist() {
    this.service.get('ipqc/finish.php?type=getdosagechklist&dosage_form='+this.selectedResult['dosage_form']).subscribe(response => {
      this.dosages = response;
    });
  }

  getdata(index) {
    index = index - 1;
    
    this.isProducts = true;
  }

  view(index) {
    this.selectedResult = this.results[index];
    // this.selectedResult = this.results[index].batches;
    // this.selectedBatch = this.selectedResult[index];
    this.getdosagechklist();
    this.isView = true;
  }

  save(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    temp['dosages']=this.dosages;
    this.service.post('batch-release.php?type=saveBatchRelease&product_code='+this.selectedResult['product_code'], JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == "success") {
        alert('Batch Release record saved successfully');
        this.router.navigate(['/batchrelease']);
      }
    });
  }

  /* shipper = 1;
  shippers = [];
  constructor(private service: DataAccessService) {
    this.calculateSippers();
  }

  ngOnInit() {
    this.getdossage();
  }
  dossage = [];
  getdossage(){
    this.service.get('qaDepartment.php?type=getDosages').subscribe((response:any) => {
      this.dossage = response;
    })
  }
  productlist =  [];
  getfinishedproduct(dosage_form){
    this.getcheckpointdata(dosage_form);
    return this.productlist = [
      {
        "id":"1",
        "product_name":"Product 1",
        "batch_no":"Batch01",
        "batch_size":"10",
        "mfg_date":"26/12/2020",
        "exp_date":"26/12/2022",
        "shippers":"10"
      },
      {
        "id":"2",
        "product_name":"Product 2",
        "batch_no":"Batch02",
        "batch_size":"10",
        "mfg_date":"26/12/2020",
        "exp_date":"26/12/2022",
        "shippers":"20"
      }
    ];
  }
  checklist = [];
  getcheckpointdata(dosage_form) {
    this.service.get('batch-release.php?type=checklist&dosage_form=' + dosage_form).subscribe((response:any) => {
      this.checklist = response;
    });
  }
  processview = false;
  processbatch(){
    this.processview = true;
  }
  submittorelease() {
    alert('Submitted, Pending For Descripancy');    
    this.processview = false;
    return;
    const temp = new FormData();
    this.service.post('batch-release.php?type=saveBatchRelease', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Batch Release saved successfully');
      } else {
        alert('An error occured, please try again!');
      }
    });
  }

  calculateSippers() {
    this.shippers = [];
    for (let i = 1; i <= this.shipper; i++) {
      this.shippers.push(i);
    }
  } */
}
