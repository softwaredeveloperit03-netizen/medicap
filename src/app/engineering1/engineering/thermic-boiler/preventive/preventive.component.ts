import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-preventive',
  templateUrl: './preventive.component.html',
  styleUrls: ['./preventive.component.css']
})
export class PreventiveComponent implements OnInit {

  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getDetails();
  }
  data;
getDetails()
{
  this.service.get('engineering/steamboiler.php?type=get_boiler_test').subscribe((response:any) => {
    this.data = response;
   
  });
}

boiler_code;
isView=false 
selectedResult=[];
view(index)
{
 this.selectedResult=this.data[index]
 this.isView=true
 this.boiler_code=this.selectedResult['boiler_code'];
}

  save(data) {
    console.log(data.value);
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
  
    this.service.post('engineering/steamboiler.php?type=save_preventive_maintainance', JSON.stringify(data.value)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Saved Successfully');
        // this.router.navigate(['/checklist']);
      } else {
        console.log(response);
        alert('Failed: An error occured, please try again!');
      }
    });
  }

}
