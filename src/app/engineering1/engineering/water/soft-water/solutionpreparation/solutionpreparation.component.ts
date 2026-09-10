import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-solutionpreparation',
  templateUrl: './solutionpreparation.component.html',
  styleUrls: ['./solutionpreparation.component.css']
})
export class SolutionpreparationComponent implements OnInit {
  isView=false
  results:any
  isNew=false
  isChecked=false
  selectedResult=[]
  constructor(private service: DataAccessService, private router: Router) {

   }
  ngOnInit(): void {
    this.getSolutionPreparationLog()
  }


  getSolutionPreparationLog() {
    this.service.get('engineering/watertank.php?type=getSolutionPreparationLog').subscribe(response => {
      this.results = response;
    })
  }
  submit(data)
  {
    let temp=data.value
    this.service.post('engineering/watertank.php?type=saveSolutionPreparation', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Record Inserted Successfully');
        data.resetForm();
        this.isView=false
        this.isNew=false
        // this.getWaterHardnessDetails()
        this.getSolutionPreparationLog()
      
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });


  }

  openChecked()
  {
    this.isNew=false
    this.isChecked=true
    this.isView=true

  
  }
  openNew()
  {
    this.isNew=true
    this.isChecked=false
    this.isView=true

  }


  closeNew()
  {
    this.isNew=false
    this.isChecked=false
    this.isView=false


  }

  closeChecked()
  {
    this.isNew=false
    this.isChecked=false
    this.isView=false

  }

  Approve(index)
  {

this.selectedResult=this.results[index]
    this.service.get('engineering/watertank.php?type=approveSlutionPreparationforSoftWater&ID='+this.selectedResult['id']).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Record Checked Successfully');
        this.isView=false
        this.isNew=false
        this.isChecked=false

        // this.getWaterHardnessDetails()
        this.getSolutionPreparationLog()
      
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });

  }
}
