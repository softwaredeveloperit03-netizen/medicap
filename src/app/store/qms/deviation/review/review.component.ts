import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-review',
  templateUrl: './review.component.html',
  styleUrls: ['./review.component.css']
})
export class ReviewComponent implements OnInit {

  isView = false;
  results;

  selectedDev = [];
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingReview();
  }

  getPendingReview() {
    this.service.get('store/deviation.php?type=getPendingReview&dept=Stores').subscribe(response => {
      this.results = response;
    });
  }

  viewDeviation(i) {
    this.selectedDev = this.results[i];
    this.isView = true;
    this.getDeviationDetails();
  }
  selectedFile:File;;
  onFileChanged(event) {
    this.selectedFile = event.target.files[0];
  }
  add(data) {
    if(!data.valid){
      alertify.error("All fields are required");
      return;
    }
    const temp = data.value;
    const uploadData = new FormData();

    for (let key in temp) {
      let value = temp[key];
      uploadData.append(key, value);
    }

    if (this.selectedFile !== undefined) {
      uploadData.append('attachment1', this.selectedFile, this.selectedFile.name);
    }

    this.service.post('qms/deviation.php?type=uploadAttachment&deviation_no='+this.selectedDev['deviation_no'],uploadData).subscribe(response => {
      if (response['status'] == 'success') {
        this.getDeviationDetails();
        alertify.success('Attachment Uploaded Successfully');
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }
  lists;
  getDeviationDetails() {
    this.service.get('store/deviation.php?type=get_deviation_files&deviation_no='+this.selectedDev['deviation_no']).subscribe((response: any) => {
      this.lists = response;
    });
  }
  viewfile(url){
    url = this.service.url + '../../upload/deviation/' + url;
    window.open(url, '_blank');
     // window.open(this.selectedResult['documents']);
  }
  save(data) {
    if (!data.valid) {
      alert('An error occured, please try again!');
      return;
    }
    let temp = data.value;
    this.service.get('deviation.php?type=saveReview&id=' + this.selectedDev['comm_no'] + '&comment=' + temp['comment'] + '&dev_no='+ this.selectedDev['dev_no']+'&cmt_id='+this.selectedDev['b_id']).subscribe(response => {
      if (response['status']) {
        alert("Review Submitted Successfully");
        this.isView = false;
        this.getPendingReview();
      } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }

}
