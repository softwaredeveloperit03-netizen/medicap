import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
@Component({
  selector: 'app-cc-primary-review',
  templateUrl: './cc-primary-review.component.html',
  styleUrls: ['./cc-primary-review.component.css'],
})
export class CcPrimaryReviewComponent implements OnInit {
  isView = false;
  results;
  ctrl_no = '';

  selectedResult = [];
  constructor(private service: DataAccessService, private router: Router) {}

  ngOnInit(): void {
    this.getCCFordeptConcern();
  }

  getCCFordeptConcern() {
    this.service.get('changecontrol1.php?type=getCCFordeptConcern&deptName='+localStorage.getItem('department')).subscribe((response) => {
        this.results = response;
      });
  }
 
  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  viewDevDoc(url) {
    url = this.service.url + '../../upload/changeControl/' + url;
   window.open(url, '_blank');
  }
 
  
  update(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
 
    let temp = data.value;
  
    this.service.post('changecontrol1.php?type=saveConcernHodComment&id=' +this.selectedResult['id'],JSON.stringify(temp)).subscribe((response) => {
        if (response['status']) {
          alert('Change Control Updated Successfully');
          this.isView = false;
          this.getCCFordeptConcern();
        } else {
          alert('Failed: An error occured, please try again!');
        }
      });
  }
}
