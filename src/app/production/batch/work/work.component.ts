import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { ActivatedRoute } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-work',
  templateUrl: './work.component.html',
  styleUrls: ['./work.component.css']
})
export class WorkComponent implements OnInit {

  isView = false;
  results;
  operators;
  work_data;

  work_allocation = '';
  workList = [] ;
  selectedWorks = [];
  selectedOperators = [];

  constructor(private service: DataAccessService, private route:ActivatedRoute, private router: Router) { }

  ngOnInit() {
    this.route.paramMap.subscribe(params => {
      this.getWorkDetails(params.get('id'));
    });
    this.getOperators();
  }

  getWorkDetails(id) {
    this.service.get('production/work.php?type=getWorkDetails&id=' + id).subscribe(response => {
      this.results = response;
      this.work_data = this.results['work_data'];
      this.isView = true;
    });
  }

  getOperators() {
    this.service.get('production/work.php?type=getOperators').subscribe(response => {
      this.operators = response;
    });
  }

  getOperatorDetails(index){
    index = index - 1;
    this.selectedOperators = this.operators[index];
  }

  addWork(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    this.workList[this.workList.length] = data.value;
    data.resetForm();
  }

  del(index) {
    this.workList.splice(index, 1);
  }

  saveWork(data) {
    if (!data.valid) {
      alert('All fields are required');
    }
    let temp = data.value;
    temp['tasks'] = this.workList;
    this.service.post('production/work.php?type=saveWork&id=' + this.results['id'],JSON.stringify(temp)).subscribe(response => {
      if (response['status'] === 'success') {
        alert('Work allocated successfully.');
        this.router.navigate(['/batch/inprocess']);
      } else {
        alert('An error Occured, Please try again!');
      }
    });
  }

}
