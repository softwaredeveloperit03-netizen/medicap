import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;


@Component({
  selector: 'app-purifiedwaterplant',
  templateUrl: './purifiedwaterplant.component.html',
  styleUrls: ['./purifiedwaterplant.component.css']
})
export class PurifiedwaterplantComponent implements OnInit {
  isView=false
    results:any

  constructor(private service: DataAccessService, private router: Router)
  {
 
   }

  ngOnInit(): void {
    this.getPurifiedWaterDetails()
  }


  

getPurifiedWaterDetails() {
  this.service.get('engineering/watertank.php?type=getPurifiedWaterplantData').subscribe(response => {
    this.results = response;
  })
}
  submit(data)
{
  let temp=data.value
  this.service.post('engineering/watertank.php?type=saveWaterPurifiedPlant', JSON.stringify(temp)).subscribe(response => {
    if (response['status'] == 'success') {
      alertify.success('Record Inserted Successfully');
      data.resetForm();
      this.isView=false
      this.getPurifiedWaterDetails();
    
    } else {
      alertify.error('Failed: An error occured, please try again!');
    }
  });


}
}
