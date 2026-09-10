import { Component, OnInit } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { Subscription, interval } from 'rxjs';
import { takeWhile } from 'rxjs/operators';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-preview-dashboard',
  templateUrl: './preview-dashboard.component.html',
  styleUrls: ['./preview-dashboard.component.css']
})
export class PreviewDashboardComponent implements OnInit {
  employeeTimeDuration: any;
  remainingTime: number = 0;
  isTimerRunning: boolean = false;
  isPaused: boolean = false;
  timerSubscription: Subscription;
  employeeData: any;

  currentPdfUrl: string | null = null;
  currentPage: number = 1;
  totalPages: number = 0; // Ensure you update this with the total number of pages
  isTimeDeviationAllowed = false;
  inComingId: any;
  pdfPath :any
  constructor(private activatedRoute: ActivatedRoute,private service:DataAccessService) {}

  ngOnInit(): void {
    const employeeData = history.state.empData;
    this.currentPdfUrl = employeeData.pdf_file;
    if (employeeData && employeeData.duration) {
      localStorage.setItem('empData', JSON.stringify(employeeData));
      this.employeeData = employeeData;
    } else {
      const storedData = localStorage.getItem('empData');
      this.employeeData = storedData ? JSON.parse(storedData) : null;
    }

    
    // For Timer
    if (this.employeeData?.duration) {
      this.employeeTimeDuration = this.employeeData?.duration;
      this.isTimeDeviationAllowed = this.employeeData?.time_deviation == "true" || this.employeeData?.time_deviation == true ? true : false;
      this.updateRemainingTime();
    }
  }

  previousPage() {
    if (this.currentPage > 1) {
      this.currentPage--;
    }
  }

  nextPage() {
    if (this.currentPage < this.totalPages) {
      this.currentPage++;
    }
  }

  // For Timer
  formatTime(seconds: number): string {
    const hours = Math.floor(seconds / 3600); // Calculate hours
    const remainingMinutes = Math.floor((seconds % 3600) / 60); // Calculate remaining minutes
    const remainingSeconds = seconds % 60;

    // Format the time based on conditions
    if (hours === 0) {
      return `${remainingMinutes
        .toString()
        .padStart(2, '0')}min:${remainingSeconds
        .toString()
        .padStart(2, '0')}sec`; // Minutes and seconds (30min:00sec)
    } else {
      return `${hours.toString().padStart(2, '0')}h:${remainingMinutes
        .toString()
        .padStart(2, '0')}min:${remainingSeconds
        .toString()
        .padStart(2, '0')}sec`; // Hours, minutes, and seconds (01h:00min:00sec)
    }
  }

  ngOnDestroy() {
    if (this.timerSubscription) {
      this.timerSubscription.unsubscribe();
    }
  }

  startTimer() {
    if (!this.isTimerRunning) {
      this.isTimerRunning = true;
      this.isPaused = false;
      this.timerSubscription = interval(1000)
        .pipe(takeWhile(() => this.remainingTime > 0))
        .subscribe(() => {
          this.remainingTime--;
        });
    }
  }

  pauseTimer() {
    if (this.isTimerRunning && !this.isPaused) {
      this.isPaused = true;
      this.timerSubscription.unsubscribe();
    }
  }

  stopTimer() {
    if (this.timerSubscription) {
      this.timerSubscription.unsubscribe();
      this.isTimerRunning = false;
      this.isPaused = false;
      this.updateRemainingTime();
    }
  }

  updateRemainingTime() {
    this.remainingTime = this.employeeTimeDuration
      ? this.employeeTimeDuration * 60
      : 0; // Convert to seconds
  }

  showPopup() {
    if (this.remainingTime === 0) {
      alert('Test Time Completed!');
      this.stopTimer(); // Ensure timer is stopped after popup
    }
  }
  // For Timer
}
