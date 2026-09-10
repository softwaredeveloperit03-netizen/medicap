import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { InterviwerComponent } from './interviwer.component';

describe('InterviwerComponent', () => {
  let component: InterviwerComponent;
  let fixture: ComponentFixture<InterviwerComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ InterviwerComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(InterviwerComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
