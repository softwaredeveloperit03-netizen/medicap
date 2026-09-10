import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
@Component({
  selector: 'app-obsolete',
  templateUrl: './obsolete.component.html',
  styleUrls: ['./obsolete.component.css']
})
export class ObsoleteComponent implements OnInit {

    constructor(private service: DataAccessService,private router: Router ) {}

  ngOnInit(): void {
  }

  
    save(data) {
      console.log(data.value);
      if (!data.valid) {
        alert('All fields are required');
        return;
      }
    
      this.service.post('qa/all.php?type=obsoluteSOP', JSON.stringify(data.value)).subscribe(response => {
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

