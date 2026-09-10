import { ComponentFixture, TestBed } from '@angular/core/testing';

import { NoncriticalComponent } from './noncritical.component';

describe('NoncriticalComponent', () => {
  let component: NoncriticalComponent;
  let fixture: ComponentFixture<NoncriticalComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ NoncriticalComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(NoncriticalComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
