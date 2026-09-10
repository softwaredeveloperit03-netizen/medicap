import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {


  material_type = '';
  rackList = [];

  constructor(private service:DataAccessService, private router:Router) { }

  ngOnInit(): void {
  }

  addRack(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    this.rackList[Object.keys(this.rackList).length] = temp;
    data.resetForm();
  }

  deleteRack(index) {
    this.rackList.splice(index, 1);
  }

  saveRack(data){
    if(!data.valid){
      alert('All fields requried!');
      return;
    }
    let temp = data.value;
    temp['rackList'] = this.rackList;
    this.service.post('qa/controlsample.php?type=saveRack', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alert( 'Data Saved Successfully');
        this.router.navigate(['/qa/controlsample/masters/rack']);
      } else {
       alert('An error has occurred, please try again!');
      }
    });
  }
  

}
