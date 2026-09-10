import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-expired',
  templateUrl: './expired.component.html',
  styleUrls: ['./expired.component.css']
})
export class ExpiredComponent implements OnInit {

  constructor(private service: DataAccessService,private router: Router ) {}

  ngOnInit(): void {
  }
  save(data) {
    if(!data.valid)
    {
      alert("All filds are Required");
      return;
    }
      this.service.post('qa/all.php?type=saveexpiry_standard_form',JSON.stringify (data.value)).subscribe(response => {
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
