import { ComponentFixture, TestBed } from '@angular/core/testing';

import { PracticalTestComponent } from './practical-test.component';

describe('PracticalTestComponent', () => {
  let component: PracticalTestComponent;
  let fixture: ComponentFixture<PracticalTestComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ PracticalTestComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(PracticalTestComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
