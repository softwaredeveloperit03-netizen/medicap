import { ComponentFixture, TestBed } from '@angular/core/testing';

import { NewTrainingAttendanceComponent } from './new-training-attendance.component';

describe('NewTrainingAttendanceComponent', () => {
  let component: NewTrainingAttendanceComponent;
  let fixture: ComponentFixture<NewTrainingAttendanceComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ NewTrainingAttendanceComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(NewTrainingAttendanceComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
