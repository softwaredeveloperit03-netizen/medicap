import { ComponentFixture, TestBed } from '@angular/core/testing';

import { SelfLearningCardsComponent } from './self-learning-cards.component';

describe('SelfLearningCardsComponent', () => {
  let component: SelfLearningCardsComponent;
  let fixture: ComponentFixture<SelfLearningCardsComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ SelfLearningCardsComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(SelfLearningCardsComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
