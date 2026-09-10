import { ComponentFixture, TestBed } from '@angular/core/testing';

import { QuestioneriesComponent } from './questioneries.component';

describe('QuestioneriesComponent', () => {
  let component: QuestioneriesComponent;
  let fixture: ComponentFixture<QuestioneriesComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ QuestioneriesComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(QuestioneriesComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
