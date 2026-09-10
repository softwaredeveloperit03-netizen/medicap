import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import { ActivatedRoute } from '@angular/router';
declare let alertify;

@Component({
  selector: 'app-edit',
  templateUrl: './edit.component.html',
  styleUrls: ['./edit.component.css']
})
export class EditComponent implements OnInit {

  isLoad = false;
  isDomastic = false;
  results;
  client_type = "DOMESTIC";

  branches = [];
  selectedBranch = [];
  isEdit = false;
  isBranch = false;
    
  constructor(private service: DataAccessService,private router: Router, private activatedRoute: ActivatedRoute) { }

  ngOnInit() {
    this.activatedRoute.paramMap.subscribe(params => {
      this.getClientDetails(params.get('id'));
    });
  }

  getClientDetails(id) {
    this.service.get('marketing/client.php?type=getClientDetails&id=' + id).subscribe(response=>{
      this.results=response;
      this.branches = this.results["branch"];
      this.isLoad = true;
    });
  }

  selectBranch(index) {
    this.selectedBranch = this.branches[index];
    this.branches.splice(index, 1);
    this.isEdit = true;
  }

  add(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    this.branches[this.branches.length] = data.value;
    this.isEdit = false;
    this.isBranch = false;
  }

  del(index) {
    this.branches.splice(index, 1);
  }
  

  submit(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    let temp = data.value;
    temp['id'] = this.results['id'];
    temp['client_code'] = this.results['client_code'];
    temp['branch'] = this.branches;
    this.service.post('marketing/client.php?type=editClient', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Record Updated Successfully');
        this.router.navigate(['/clients']);
      } else {
        alertify.error('Please try Again');
      }
    });
  }

}
