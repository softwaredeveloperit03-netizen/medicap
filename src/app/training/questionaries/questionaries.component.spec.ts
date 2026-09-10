import { ComponentFixture, TestBed } from '@angular/core/testing';

import { QuestionariesComponent } from './questionaries.component';

describe('QuestionariesComponent', () => {
  let component: QuestionariesComponent;
  let fixture: ComponentFixture<QuestionariesComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ QuestionariesComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(QuestionariesComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
