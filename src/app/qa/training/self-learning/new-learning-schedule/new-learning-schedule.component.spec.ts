import { ComponentFixture, TestBed } from '@angular/core/testing';

import { NewLearningScheduleComponent } from './new-learning-schedule.component';

describe('NewLearningScheduleComponent', () => {
  let component: NewLearningScheduleComponent;
  let fixture: ComponentFixture<NewLearningScheduleComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ NewLearningScheduleComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(NewLearningScheduleComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
