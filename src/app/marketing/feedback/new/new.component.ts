import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {

  clients: any[] = [];
  client_code = '';

  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit() {
    this.getclientlist();
  }

  getclientlist() {
    this.service.get('common.php?type=getClients').subscribe({
      next: (response: any) => {
        this.clients = Array.isArray(response) ? response : [];
      },
      error: () => {
        this.clients = [];
      },
    });
  }

  savefeedback(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    this.service.post('marketing/feedback.php?type=saveFeedback', JSON.stringify(data.value)).subscribe((response: any) => {
      if (response?.status === 'success') {
        data.reset();
        this.client_code = '';
        alert('Saved successfully');
        this.router.navigate(['/marketing/feedback/log']);
      } else {
        alert('Failed to save feedback. Please try again.');
      }
    });
  }

}
