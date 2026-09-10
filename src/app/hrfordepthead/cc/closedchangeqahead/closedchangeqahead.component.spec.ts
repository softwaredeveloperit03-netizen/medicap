import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ClosedchangeqaheadComponent } from './closedchangeqahead.component';

describe('ClosedchangeqaheadComponent', () => {
  let component: ClosedchangeqaheadComponent;
  let fixture: ComponentFixture<ClosedchangeqaheadComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ ClosedchangeqaheadComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ClosedchangeqaheadComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
