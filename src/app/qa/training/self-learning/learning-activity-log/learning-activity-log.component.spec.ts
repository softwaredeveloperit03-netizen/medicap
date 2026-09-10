import { ComponentFixture, TestBed } from '@angular/core/testing';

import { LearningActivityLogComponent } from './learning-activity-log.component';

describe('LearningActivityLogComponent', () => {
  let component: LearningActivityLogComponent;
  let fixture: ComponentFixture<LearningActivityLogComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ LearningActivityLogComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(LearningActivityLogComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
