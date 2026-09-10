import { ComponentFixture, TestBed } from '@angular/core/testing';

import { SafedepositlogComponent } from './safedepositlog.component';

describe('SafedepositlogComponent', () => {
  let component: SafedepositlogComponent;
  let fixture: ComponentFixture<SafedepositlogComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ SafedepositlogComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(SafedepositlogComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
