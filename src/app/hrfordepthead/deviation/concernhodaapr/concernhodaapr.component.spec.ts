import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ConcernhodaaprComponent } from './concernhodaapr.component';

describe('ConcernhodaaprComponent', () => {
  let component: ConcernhodaaprComponent;
  let fixture: ComponentFixture<ConcernhodaaprComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ ConcernhodaaprComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ConcernhodaaprComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
