import { ComponentFixture, TestBed } from '@angular/core/testing';

import { QAccCommentComponent } from './qacc-comment.component';

describe('QAccCommentComponent', () => {
  let component: QAccCommentComponent;
  let fixture: ComponentFixture<QAccCommentComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ QAccCommentComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(QAccCommentComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
