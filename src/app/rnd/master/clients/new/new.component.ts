import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify: any;
@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {

  isDomastic = false;

  agents;
  refered_by = 'Direct Customer';
  client_type = 'Distributor';
  order_category = "DOMESTIC";
  gst_type = 'IGST';

  branches = [];
  divisions = [];
  selected=[];
  state;
  selectedstate=[];

  isBranch = false;

    
  constructor(private service: DataAccessService,private router: Router) { }

  ngOnInit() {
    this.getStates();
  }


  getStates(){
    this.service.get('common.php?type=getStates').subscribe(response=>{
      this.state=response;
    });
  }

  getStateData(index){
    index=index-1;
    if(index !== -1){
      this.selectedstate=this.state[index];
    }
  }

  getPercentage(index){
    if(index!==-1){  
      this.selected=this.agents[index];
    }
  }



  submit(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    let temp = data.value;
    temp['branch'] = this.branches;
    temp['divisions'] = this.divisions;
    
    this.service.post('marketing/client.php?type=saveClient', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Record Inserted Successfully');
        data.resetForm();
        this.branches=[];
        this.router.navigate(['/clients']);
      } else {
        alertify.error('Please try Again');
      }
    });
  }

  add(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    if (data.value.gst_type == 'Exempted' || data.value.gst_type == 'Non Registered') {
      data.value.gst_no = 'NA';
    }
    let temp=data.value;
    temp['state_name']=this.selectedstate['state_name'];
    this.branches[this.branches.length] = temp;
    data.resetForm();
    this.isBranch = false;
  }

  addDivision(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    this.divisions[this.divisions.length] = data.value;
    data.resetForm();
  }

  delDivision(index) {
    this.divisions.splice(index, 1);
  }

}
