import { ComponentFixture, TestBed } from '@angular/core/testing';

import { TrainingAttendanceComponent } from './training-attendance.component';

describe('TrainingAttendanceComponent', () => {
  let component: TrainingAttendanceComponent;
  let fixture: ComponentFixture<TrainingAttendanceComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ TrainingAttendanceComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(TrainingAttendanceComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
