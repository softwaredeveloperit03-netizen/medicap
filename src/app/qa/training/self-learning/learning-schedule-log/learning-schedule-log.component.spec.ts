import { ComponentFixture, TestBed } from '@angular/core/testing';

import { LearningScheduleLogComponent } from './learning-schedule-log.component';

describe('LearningScheduleLogComponent', () => {
  let component: LearningScheduleLogComponent;
  let fixture: ComponentFixture<LearningScheduleLogComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ LearningScheduleLogComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(LearningScheduleLogComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
