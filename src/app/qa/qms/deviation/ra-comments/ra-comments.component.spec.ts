import { ComponentFixture, TestBed } from '@angular/core/testing';

import { RaCommentsComponent } from './ra-comments.component';

describe('RaCommentsComponent', () => {
  let component: RaCommentsComponent;
  let fixture: ComponentFixture<RaCommentsComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ RaCommentsComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(RaCommentsComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
