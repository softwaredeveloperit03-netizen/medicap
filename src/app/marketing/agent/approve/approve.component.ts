import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-approve',
  templateUrl: './approve.component.html',
  styleUrls: ['./approve.component.css']
})
export class ApproveComponent implements OnInit {
  results
  selectresult =[];
  isView= false;
  constructor(private service:DataAccessService) { }

  ngOnInit() {
    this.getPendingAgents();
  }

getPendingAgents(){
  this.service.get('marketing/agent.php?type=getPendingAgents').subscribe(response =>{
    this.results =response;
  });
  }

  view(index){
    this.selectresult = this.results[index];
    this.isView = true;
  }

  update(status) {
    this.service.get('marketing/agent.php?type=updateAgent&status=' + status + '&id=' + this.selectresult['id']).subscribe(response => {
      if (response['status'] =='success') {
        alert('Agent Updated Successfully');
        this.isView = false;
        this.getPendingAgents();
      } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }

}
