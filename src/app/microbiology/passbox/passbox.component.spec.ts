import { ComponentFixture, TestBed } from '@angular/core/testing';

import { PassboxComponent } from './passbox.component';

describe('PassboxComponent', () => {
  let component: PassboxComponent;
  let fixture: ComponentFixture<PassboxComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ PassboxComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(PassboxComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
