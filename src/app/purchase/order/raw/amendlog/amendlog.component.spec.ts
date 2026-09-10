import { ComponentFixture, TestBed } from '@angular/core/testing';

import { AmendlogComponent } from './amendlog.component';

describe('AmendlogComponent', () => {
  let component: AmendlogComponent;
  let fixture: ComponentFixture<AmendlogComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ AmendlogComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(AmendlogComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
